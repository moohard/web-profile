<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use Inertia\Ssr\HttpGateway;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

it('Publik layout mengirim props region, menu, dan locales', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->has('locale')
            ->has('locales')
            ->has('headerMenu')
            ->has('footerMenu')
            ->has('region.widgets.beforeContent')
            ->has('region.widgets.afterContent')
            ->has('region.widgets.sidebar')
            ->has('region.widgets.footer')
            ->where('locales', function ($locales) {
                $names = collect($locales)->pluck('name')->all();

                return in_array('Bahasa Indonesia', $names, true)
                    && in_array('English', $names, true);
            })
        );
});

it('halaman dengan hero & sidebar aktif mengirim region.hero dan region.sidebar', function () {
    $langId = Language::idFor('id');
    $page = Page::create([
        'mode' => 'Template',
        'hero_enabled' => true,
        'sidebar_enabled' => true,
    ]);
    PageTranslation::create([
        'page_id' => $page->id,
        'language_id' => $langId,
        'slug' => 'profil',
        'title' => 'Profil',
        'hero_heading' => 'Selamat Datang',
        'hero_subheading' => 'Sub judul',
        'hero_cta_text' => 'Mulai',
        'hero_cta_link' => '/mulai',
        'status' => 'Published',
    ]);

    $this->get('/profil')->assertInertia(fn (Assert $inertia) => $inertia
        ->component('public/page-show')
        ->where('region.hero.enabled', true)
        ->where('region.hero.heading', 'Selamat Datang')
        ->where('region.hero.ctaLink', '/mulai')
        ->where('region.sidebar.enabled', true)
    );
});

it('Publik layout punya header, main id, footer', function () {
    $response = $this->get('/');
    $html = $response->getContent();

    $response->assertOk();

    // Struktur HTML hanya tersedia saat Inertia SSR aktif
    if (! app(HttpGateway::class)->isHealthy()) {
        $this->markTestSkipped('Inertia SSR server tidak berjalan — jalankan: php artisan inertia:start-ssr');
    }

    expect($html)->toContain('<header')
        ->and($html)->toContain('id="main-content"')
        ->and($html)->toContain('<footer');
});

it('Skip link aksesibilitas ada', function () {
    $html = $this->get('/')->getContent();

    if (! app(HttpGateway::class)->isHealthy()) {
        $this->markTestSkipped('Inertia SSR server tidak berjalan — jalankan: php artisan inertia:start-ssr');
    }

    expect($html)->toMatch('/sr-only.*Lewati ke konten/i');
});

it('LocaleSwitcher render dua locale', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('Bahasa Indonesia')->toContain('English');
});
