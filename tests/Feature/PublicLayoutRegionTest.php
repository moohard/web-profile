<?php

use App\Models\Language;
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
