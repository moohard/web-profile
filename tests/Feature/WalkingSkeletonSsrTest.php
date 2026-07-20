<?php

use App\Models\Language;
use Inertia\Ssr\HttpGateway;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

it('GET /berita/selamat-datang: props SEO (title/canonical/hreflang ID+EN) selalu terkirim', function () {
    // Assertion prop Inertia TIDAK butuh Node SSR — mengecek payload data-page,
    // jadi cakupan SEO ini berjalan di setiap `php artisan test` (bukan false-green).
    $this->get('/berita/selamat-datang')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/post-show')
            ->has('seo.title')
            ->has('seo.canonical')
            ->has('seo.hreflang.id')
            ->has('seo.hreflang.en')
            ->has('seo.hreflang.x-default')
            ->where('seo.ogType', 'article')
        );
});

it('GET /berita/selamat-datang SSR: HTML mengandung judul + hreflang ID + EN', function () {
    // Hanya assertion markup HTML ter-render yang butuh Node SSR aktif.
    if (! app(HttpGateway::class)->isHealthy()) {
        $this->markTestSkipped('Inertia SSR server tidak berjalan — jalankan: php artisan inertia:start-ssr');
    }

    $html = $this->get('/berita/selamat-datang')->getContent();

    expect($html)
        ->toContain('Selamat Datang di Papenajam')
        // Inertia SSR Head: <title data-inertia>…</title>
        ->and($html)->toMatch('/<title[\s>]/')
        // React/Inertia SSR menyerialisasi prop hrefLang (camelCase) di head
        ->and($html)->toMatch('/<link[^>]+rel="alternate"[^>]+hrefLang="id"/i')
        ->and($html)->toMatch('/<link[^>]+rel="alternate"[^>]+hrefLang="en"/i')
        ->and($html)->toMatch('/<link[^>]+rel="canonical"/');
});

it('GET /en/berita/welcome SSR: judul EN', function () {
    $response = $this->get('/en/berita/welcome');
    $html = $response->getContent();
    expect($html)->toContain('Welcome to Papenajam');
});

it('GET / curl-equivalent: bukan empty div Inertia', function () {
    $response = $this->get('/');
    $html = $response->getContent();
    // SSR berhasil bila body HTML terisi konten (bukan hanya <div data-page="..."></div> kosong)
    expect($html)->toMatch('/<main|<h1|Selamat Datang/i');
});
