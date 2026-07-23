<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Support\PublicLayoutProps;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
    PublicLayoutProps::flushCache();
});

it('melayani bahasa aktif baru tanpa mengubah route hardcode', function () {
    Language::factory()->create([
        'code' => 'fr',
        'name' => 'Français',
        'is_active' => true,
        'sort_order' => 3,
    ]);
    Language::flushCache();

    $this->get('/fr')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->where('locale', 'fr')
        );
});

it('menggunakan prefix untuk bahasa non-default setelah default berubah', function () {
    Language::query()->update(['is_default' => false]);
    Language::query()->where('code', 'en')->update([
        'is_default' => true,
        'is_active' => true,
    ]);
    Language::flushCache();

    $this->get('/id')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'id')
            ->where('localeLinks.0.code', 'en')
            ->where('localeLinks.0.isCurrent', false)
            ->where('localeLinks.1.code', 'id')
            ->where('localeLinks.1.isCurrent', true)
        );

    $this->get('/en')
        ->assertRedirect('/');
});

it('mengarahkan prefix default ke canonical tanpa prefix dan mempertahankan query', function () {
    $this->get('/id/profil?utm=abc')
        ->assertRedirect('/profil?utm=abc')
        ->assertStatus(301);
});

it('menolak prefix bahasa inactive', function () {
    $french = Language::factory()->create([
        'code' => 'fr',
        'is_active' => false,
        'sort_order' => 3,
    ]);
    PageTranslation::factory()->create([
        'page_id' => Page::factory()->create()->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'fr',
        'status' => 'Published',
    ]);
    Language::flushCache();

    $this->get('/fr')->assertNotFound();

    PageTranslation::factory()->create([
        'page_id' => Page::factory()->create()->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'xx',
        'status' => 'Published',
    ]);

    $this->get('/xx')->assertOk();
    expect($french->is_active)->toBeFalse();
});

it('meresolve slug localized hanya pada bahasa aktif yang sesuai', function () {
    $french = Language::factory()->create([
        'code' => 'fr',
        'name' => 'Français',
        'is_active' => true,
        'sort_order' => 3,
    ]);
    $page = Page::factory()->create();

    PageTranslation::factory()->create([
        'page_id' => $page->id,
        'language_id' => Language::idFor('id'),
        'slug' => 'profil',
        'status' => 'Published',
    ]);
    PageTranslation::factory()->create([
        'page_id' => $page->id,
        'language_id' => $french->id,
        'slug' => 'a-propos',
        'status' => 'Published',
    ]);
    Language::flushCache();

    $this->get('/fr/a-propos')
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('public/page-show')
            ->where('locale', 'fr')
        );

    $this->get('/fr/profil')->assertNotFound();
});

it('tidak menangkap route sistem sebagai route publik', function () {
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/login')->assertOk();
});
