<?php

use App\Models\Language;
use Inertia\Ssr\HttpGateway;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

it('GET /berita/selamat-datang SSR: HTML mengandung judul + hreflang ID + EN', function () {
    // Walking skeleton SSR membutuhkan Node SSR server (`php artisan inertia:start-ssr`).
    if (! app(HttpGateway::class)->isHealthy()) {
        $this->markTestSkipped('Inertia SSR server tidak berjalan — jalankan: php artisan inertia:start-ssr');
    }

    $response = $this->get('/berita/selamat-datang');
    $html = $response->getContent();

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/post-show')
            ->has('seo.title')
            ->has('seo.canonical')
            ->has('seo.hreflang.id')
            ->has('seo.hreflang.en')
            ->where('seo.ogType', 'article')
        );

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
