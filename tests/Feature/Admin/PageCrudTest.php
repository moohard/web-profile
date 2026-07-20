<?php

declare(strict_types=1);

use App\Enums\PageMode;
use App\Enums\UserRole;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->idLang = Language::idFor('id');
    $this->enLang = Language::idFor('en');
});

function pageAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

function pageEditor(): User
{
    return User::factory()->create()->assignRole(UserRole::Editor->value);
}

it('GET /admin/pages menampilkan daftar halaman untuk admin', function () {
    Page::factory()->has(PageTranslation::factory()->state(['language_id' => $this->idLang]), 'translations')->create();

    $this->actingAs(pageAdmin())
        ->get('/admin/pages')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pages/index')
            ->has('pages.data')
        );
});

it('GET /admin/pages menampilkan daftar halaman untuk editor', function () {
    Page::factory()->has(PageTranslation::factory()->state(['language_id' => $this->idLang]), 'translations')->create();

    $this->actingAs(pageEditor())
        ->get('/admin/pages')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pages/index')
            ->has('pages.data')
        );
});

it('Filter ?status menyaring halaman berdasarkan status', function () {
    $draft = Page::factory()->create();
    PageTranslation::factory()->for($draft)->create(['language_id' => $this->idLang, 'status' => 'Draft']);

    $published = Page::factory()->create();
    PageTranslation::factory()->for($published)->create(['language_id' => $this->idLang, 'status' => 'Published']);

    $this->actingAs(pageAdmin())
        ->get('/admin/pages?status=Draft')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('pages.data', 1)
            ->where('pages.data.0.status', 'Draft')
        );
});

it('User dengan hanya access-admin (tanpa pages.viewAny) ditolak melihat daftar halaman', function () {
    $user = User::factory()->create()->givePermissionTo('access-admin');

    $this->actingAs($user)
        ->get('/admin/pages')
        ->assertForbidden();
});

it('POST /admin/pages membuat halaman beserta translation per bahasa yang diisi', function () {
    $response = $this->actingAs(pageAdmin())->post('/admin/pages', [
        'mode' => PageMode::Template->value,
        'template_key' => 'default',
        'hero_enabled' => true,
        'hero_image' => 'https://example.test/media/hero.jpg',
        'sidebar_enabled' => false,
        'translations' => [
            [
                'language_id' => $this->idLang,
                'title' => 'Halaman Tentang',
                'slug' => '',
                'content' => '<p>Isi halaman</p>',
                'status' => 'Published',
                'hero_heading' => 'Selamat datang',
                'hero_subheading' => 'Subjudul',
                'hero_cta_text' => 'Klik',
                'hero_cta_link' => '/kontak',
                'meta_title' => 'Meta title',
                'meta_description' => 'Meta description',
            ],
            [
                'language_id' => $this->enLang,
                'title' => 'About Page',
                'slug' => '',
                'content' => '<p>Page content</p>',
                'status' => 'Draft',
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.pages.index'));

    $page = Page::query()->latest('id')->first();

    expect($page)->not->toBeNull()
        ->and($page->mode)->toBe(PageMode::Template)
        ->and($page->template_key)->toBe('default')
        ->and($page->hero_enabled)->toBeTrue()
        ->and($page->hero_image)->toBe('https://example.test/media/hero.jpg')
        ->and($page->sidebar_enabled)->toBeFalse()
        ->and($page->translations()->count())->toBe(2);

    $idTranslation = PageTranslation::query()
        ->where('page_id', $page->id)
        ->where('language_id', $this->idLang)
        ->first();

    expect($idTranslation->title)->toBe('Halaman Tentang')
        ->and($idTranslation->slug)->toBe('halaman-tentang')
        ->and($idTranslation->content)->toBe(['html' => '<p>Isi halaman</p>'])
        ->and($idTranslation->status)->toBe('Published')
        ->and($idTranslation->hero_heading)->toBe('Selamat datang')
        ->and($idTranslation->meta_title)->toBe('Meta title');
});

it('content halaman disanitasi sebelum disimpan', function () {
    $this->actingAs(pageAdmin())->post('/admin/pages', [
        'mode' => PageMode::Template->value,
        'template_key' => 'default',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Aman',
            'content' => '<script>alert(1)</script><p>ok</p>',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    $translation = PageTranslation::query()->latest('id')->first();

    expect($translation->content['html'])->not->toContain('<script>')
        ->and($translation->content['html'])->toBe('<p>ok</p>');
});

it('slug halaman otomatis dibuat dari judul dan tetap unik per bahasa', function () {
    $this->actingAs(pageAdmin())->post('/admin/pages', [
        'mode' => PageMode::Template->value,
        'template_key' => 'default',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Sama Saja',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    $this->actingAs(pageAdmin())->post('/admin/pages', [
        'mode' => PageMode::Template->value,
        'template_key' => 'default',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Sama Saja',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    $slugs = PageTranslation::query()
        ->where('language_id', $this->idLang)
        ->where('title', 'Sama Saja')
        ->pluck('slug')
        ->all();

    expect($slugs)->toContain('sama-saja')
        ->and($slugs)->toContain('sama-saja-2');
});

it('PUT /admin/pages/{page} meng-upsert translations dan ganti hero image', function () {
    $page = Page::factory()->create(['hero_image' => 'https://example.test/media/old.jpg']);
    $existingTranslation = PageTranslation::factory()->for($page)->create(['language_id' => $this->idLang, 'title' => 'Lama']);

    $this->actingAs(pageAdmin())->put("/admin/pages/{$page->id}", [
        'mode' => PageMode::Template->value,
        'template_key' => 'landing',
        'hero_enabled' => true,
        'hero_image' => 'https://example.test/media/new.jpg',
        'sidebar_enabled' => true,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Baru',
            'slug' => $existingTranslation->slug,
            'content' => '<p>Baru</p>',
            'status' => 'Published',
        ]],
    ])->assertRedirect(route('admin.pages.index'));

    $fresh = $page->fresh();

    expect($fresh->template_key)->toBe('landing')
        ->and($fresh->hero_enabled)->toBeTrue()
        ->and($fresh->hero_image)->toBe('https://example.test/media/new.jpg')
        ->and($fresh->sidebar_enabled)->toBeTrue()
        ->and($fresh->translations()->count())->toBe(1)
        ->and($fresh->translations()->first()->title)->toBe('Baru')
        ->and($fresh->translations()->first()->slug)->toBe($existingTranslation->slug);
});

it('DELETE oleh Admin menghapus halaman', function () {
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create(['language_id' => $this->idLang]);

    $this->actingAs(pageAdmin())
        ->delete("/admin/pages/{$page->id}")
        ->assertRedirect();

    expect(Page::find($page->id))->toBeNull();
});

it('DELETE oleh Editor menghapus halaman', function () {
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create(['language_id' => $this->idLang]);

    $this->actingAs(pageEditor())
        ->delete("/admin/pages/{$page->id}")
        ->assertRedirect();

    expect(Page::find($page->id))->toBeNull();
});

it('validasi menolak bila bahasa default belum terisi judulnya', function () {
    $countBefore = Page::query()->count();

    $this->actingAs(pageAdmin())->post('/admin/pages', [
        'mode' => PageMode::Template->value,
        'template_key' => 'default',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
        'translations' => [[
            'language_id' => $this->enLang,
            'title' => 'Only English',
            'status' => 'Draft',
        ]],
    ])->assertSessionHasErrors('translations');

    expect(Page::query()->count())->toBe($countBefore);
});
