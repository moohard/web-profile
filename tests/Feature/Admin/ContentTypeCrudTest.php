<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\ContentTypeTranslation;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Models\WritingStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

function contentTypeAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('GET /admin/content-types menampilkan daftar jenis konten untuk admin', function () {
    $this->actingAs(contentTypeAdmin())
        ->get('/admin/content-types')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content-types/index')
            ->has('contentTypes')
            ->has('languages')
        );
});

it('GET /admin/content-types/create menampilkan form pembuatan', function () {
    $this->actingAs(contentTypeAdmin())
        ->get('/admin/content-types/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content-types/form')
            ->where('contentType', null)
            ->has('languages')
            ->has('writingStyles')
        );
});

it('POST /admin/content-types membuat jenis konten beserta translation, is_active, dan writing_style_id', function () {
    $idLang = Language::idFor('id');
    $enLang = Language::idFor('en');
    $writingStyle = WritingStyle::factory()->create();

    $this->actingAs(contentTypeAdmin())
        ->post('/admin/content-types', [
            'writing_style_id' => $writingStyle->id,
            'archive_template_key' => 'default',
            'single_template_key' => 'default',
            'is_active' => true,
            'sort_order' => 3,
            'translations' => [
                ['language_id' => $idLang, 'name' => 'Ulasan', 'description' => 'Deskripsi ulasan'],
                ['language_id' => $enLang, 'name' => 'Review', 'description' => 'Review description'],
            ],
        ])
        ->assertRedirect(route('admin.content-types.index'));

    $contentType = ContentType::query()->where('slug', 'ulasan')->first();

    expect($contentType)->not->toBeNull()
        ->and($contentType->is_active)->toBeTrue()
        ->and($contentType->writing_style_id)->toBe($writingStyle->id)
        ->and($contentType->translations()->count())->toBe(2)
        ->and(ContentTypeTranslation::query()->where('content_type_id', $contentType->id)->where('language_id', $idLang)->value('name'))->toBe('Ulasan')
        ->and(ContentTypeTranslation::query()->where('content_type_id', $contentType->id)->where('language_id', $idLang)->value('description'))->toBe('Deskripsi ulasan')
        ->and(ContentTypeTranslation::query()->where('content_type_id', $contentType->id)->where('language_id', $enLang)->value('name'))->toBe('Review');
});

it('POST /admin/content-types membust cache sidebar & layout publik', function () {
    $idLang = Language::idFor('id');
    $enLang = Language::idFor('en');

    Cache::put('inertia.content_types.id', ['stale'], now()->addHour());
    Cache::put('inertia.content_types.en', ['stale'], now()->addHour());
    Cache::put('public_layout.'.$idLang, ['stale'], now()->addHour());
    Cache::put('public_layout.'.$enLang, ['stale'], now()->addHour());

    $this->actingAs(contentTypeAdmin())
        ->post('/admin/content-types', [
            'translations' => [
                ['language_id' => $idLang, 'name' => 'Galeri Foto'],
                ['language_id' => $enLang, 'name' => 'Photo Gallery'],
            ],
        ])
        ->assertRedirect();

    expect(Cache::has('inertia.content_types.id'))->toBeFalse()
        ->and(Cache::has('inertia.content_types.en'))->toBeFalse()
        ->and(Cache::has('public_layout.'.$idLang))->toBeFalse()
        ->and(Cache::has('public_layout.'.$enLang))->toBeFalse();
});

it('GET /admin/content-types/{contentType}/edit menampilkan form dengan data', function () {
    $idLang = Language::idFor('id');
    $contentType = ContentType::factory()->withTranslation('id', $idLang, ['name' => 'Lama'])->create(['slug' => 'lama']);

    $this->actingAs(contentTypeAdmin())
        ->get("/admin/content-types/{$contentType->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/content-types/form')
            ->where('contentType.id', $contentType->id)
            ->where('contentType.slug', 'lama')
        );
});

it('PUT /admin/content-types/{contentType} memperbarui jenis konten dan translation', function () {
    $idLang = Language::idFor('id');
    $contentType = ContentType::factory()->withTranslation('id', $idLang, ['name' => 'Lama'])->create(['slug' => 'lama', 'is_active' => true]);

    $this->actingAs(contentTypeAdmin())
        ->put("/admin/content-types/{$contentType->id}", [
            'slug' => 'baru',
            'is_active' => false,
            'sort_order' => 7,
            'translations' => [
                ['language_id' => $idLang, 'name' => 'Baru', 'description' => 'Deskripsi baru'],
            ],
        ])
        ->assertRedirect(route('admin.content-types.index'));

    $fresh = $contentType->fresh();

    expect($fresh->slug)->toBe('baru')
        ->and($fresh->is_active)->toBeFalse()
        ->and($fresh->sort_order)->toBe(7)
        ->and($fresh->translations()->where('language_id', $idLang)->value('name'))->toBe('Baru')
        ->and($fresh->translations()->where('language_id', $idLang)->value('description'))->toBe('Deskripsi baru');
});

it('DELETE ditolak bila jenis konten masih memiliki post', function () {
    $idLang = Language::idFor('id');
    $contentType = ContentType::factory()->withTranslation('id', $idLang)->create();
    Post::factory()->create(['type_id' => $contentType->id]);

    $this->actingAs(contentTypeAdmin())
        ->delete("/admin/content-types/{$contentType->id}")
        ->assertRedirect();

    expect(ContentType::find($contentType->id))->not->toBeNull();
});

it('DELETE ditolak bila jenis konten masih memiliki post yang sudah di-trash (soft-deleted)', function () {
    // Guard sebelumnya buta trashed: posts()->exists() tak melihat post yang
    // sudah di-soft-delete (SoftDeletes menambah global scope exclude), jadi
    // ContentType lolos terhapus → cascadeOnDelete pada posts.type_id
    // HARD-DELETE post trashed itu (bypass forceDelete/policy). Post trashed
    // tetap harus MENGUNCI penghapusan sampai di-forceDelete.
    $idLang = Language::idFor('id');
    $contentType = ContentType::factory()->withTranslation('id', $idLang)->create();
    $post = Post::factory()->create(['type_id' => $contentType->id]);
    $post->delete();

    $this->actingAs(contentTypeAdmin())
        ->delete("/admin/content-types/{$contentType->id}")
        ->assertRedirect();

    expect(ContentType::find($contentType->id))->not->toBeNull();
    expect(Post::onlyTrashed()->find($post->id))->not->toBeNull();
});

it('DELETE menghapus jenis konten tanpa post terkait', function () {
    $idLang = Language::idFor('id');
    $contentType = ContentType::factory()->withTranslation('id', $idLang)->create();

    $this->actingAs(contentTypeAdmin())
        ->delete("/admin/content-types/{$contentType->id}")
        ->assertRedirect();

    expect(ContentType::find($contentType->id))->toBeNull();
});

it('User tanpa content-types.viewAny mendapat 403', function () {
    $user = User::factory()->create()->givePermissionTo('access-admin');
    $idLang = Language::idFor('id');
    $contentType = ContentType::factory()->withTranslation('id', $idLang)->create();

    $this->actingAs($user)->get('/admin/content-types')->assertForbidden();
    $this->actingAs($user)->get('/admin/content-types/create')->assertForbidden();
    $this->actingAs($user)->post('/admin/content-types', [
        'translations' => [['language_id' => $idLang, 'name' => 'X']],
    ])->assertForbidden();
    $this->actingAs($user)->get("/admin/content-types/{$contentType->id}/edit")->assertForbidden();
    $this->actingAs($user)->put("/admin/content-types/{$contentType->id}", [
        'translations' => [['language_id' => $idLang, 'name' => 'X']],
    ])->assertForbidden();
    $this->actingAs($user)->delete("/admin/content-types/{$contentType->id}")->assertForbidden();
});
