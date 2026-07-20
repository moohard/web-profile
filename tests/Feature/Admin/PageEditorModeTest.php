<?php

declare(strict_types=1);

use App\Enums\PageMode;
use App\Enums\UserRole;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->idLang = Language::idFor('id');
});

function modeAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

function modeEditor(): User
{
    return User::factory()->create()->assignRole(UserRole::Editor->value);
}

it('Admin boleh menyimpan halaman dengan mode Code', function () {
    $response = $this->actingAs(modeAdmin())->post('/admin/pages', [
        'mode' => PageMode::Code->value,
        'template_key' => 'default',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Halaman Code',
            'content' => '<div class="hero">Halo</div>',
            'status' => 'Draft',
        ]],
    ]);

    $response->assertRedirect(route('admin.pages.index'));

    $page = Page::query()->latest('id')->first();

    expect($page->mode)->toBe(PageMode::Code);
});

it('Editor (non-admin) menyimpan halaman dengan mode Code ditolak', function () {
    $countBefore = Page::query()->count();

    $this->actingAs(modeEditor())->post('/admin/pages', [
        'mode' => PageMode::Code->value,
        'template_key' => 'default',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Halaman Code Editor',
            'content' => '<div>Halo</div>',
            'status' => 'Draft',
        ]],
    ])->assertForbidden();

    expect(Page::query()->count())->toBe($countBefore);
});

it('Editor boleh menyimpan halaman dengan mode Template', function () {
    $response = $this->actingAs(modeEditor())->post('/admin/pages', [
        'mode' => PageMode::Template->value,
        'template_key' => 'landing',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Halaman Template Editor',
            'status' => 'Draft',
        ]],
    ]);

    $response->assertRedirect(route('admin.pages.index'));

    $page = Page::query()->latest('id')->first();

    expect($page->mode)->toBe(PageMode::Template);
});

it('canUseCodeMode true untuk Admin pada form create', function () {
    $this->actingAs(modeAdmin())
        ->get('/admin/pages/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pages/form')
            ->where('canUseCodeMode', true)
        );
});

it('canUseCodeMode false untuk Editor pada form create', function () {
    $this->actingAs(modeEditor())
        ->get('/admin/pages/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pages/form')
            ->where('canUseCodeMode', false)
        );
});

it('Editor mengubah halaman existing ke mode Code ditolak', function () {
    $page = Page::factory()->create(['mode' => PageMode::Template]);

    $this->actingAs(modeEditor())->put("/admin/pages/{$page->id}", [
        'mode' => PageMode::Code->value,
        'template_key' => 'default',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Ubah ke Code',
            'status' => 'Draft',
        ]],
    ])->assertForbidden();

    expect($page->fresh()->mode)->toBe(PageMode::Template);
});
