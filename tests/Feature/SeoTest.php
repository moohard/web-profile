<?php

use App\Models\Language;
use App\Support\Seo\SeoProps;
use Inertia\Ssr\HttpGateway;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

it('SeoProps::for membangun array meta dan OG', function () {
    $seo = SeoProps::for(
        title: 'Judul',
        description: 'Deskripsi',
        canonical: 'https://example.test/berita/a',
        hreflang: ['id' => 'https://example.test/berita/a'],
        ogType: 'article',
        ogImage: 'https://example.test/img.jpg',
    );

    expect($seo)->toMatchArray([
        'title' => 'Judul',
        'description' => 'Deskripsi',
        'canonical' => 'https://example.test/berita/a',
        'hreflang' => ['id' => 'https://example.test/berita/a'],
        'ogType' => 'article',
        'ogImage' => 'https://example.test/img.jpg',
        'ogTitle' => 'Judul',
        'ogDescription' => 'Deskripsi',
    ]);
});

it('SeoProps::withXDefault menunjuk bahasa default, bukan entri pertama array', function () {
    // 'en' sengaja ditaruh pertama untuk membuktikan x-default TIDAK memakai urutan array.
    $hreflang = SeoProps::withXDefault([
        'en' => 'https://example.test/en/profile',
        'id' => 'https://example.test/profil',
    ]);

    expect($hreflang)->toHaveKey('x-default')
        ->and($hreflang['x-default'])->toBe('https://example.test/profil');
});

it('SeoProps::withXDefault mengembalikan map kosong apa adanya', function () {
    expect(SeoProps::withXDefault([]))->toBe([]);
});

it('Home mengandung JSON-LD Organization dan WebSite', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->has('jsonLd.@context')
            ->has('jsonLd.@graph')
            ->where('jsonLd.@graph.0.@type', 'Organization')
            ->where('jsonLd.@graph.1.@type', 'WebSite')
        );

    // Tag script JSON-LD di HTML butuh Inertia SSR
    if (! app(HttpGateway::class)->isHealthy()) {
        return;
    }

    $html = $response->getContent();
    expect($html)->toMatch('/application\/ld\+json/')
        ->and($html)->toContain('"@type":"Organization"')
        ->and($html)->toContain('"@type":"WebSite"');
});

it('Single post mengandung JSON-LD Article', function () {
    $response = $this->get('/berita/selamat-datang');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-show')
            ->has('jsonLd')
            ->where('jsonLd.@type', 'Article')
            ->has('jsonLd.headline')
            ->where('seo.ogType', 'article')
            ->has('seo.ogTitle')
        );

    if (! app(HttpGateway::class)->isHealthy()) {
        return;
    }

    $html = $response->getContent();
    expect($html)->toContain('"@type":"Article"')
        ->and($html)->toContain('"headline"');
});

it('Meta description dan canonical ada di single post', function () {
    $response = $this->get('/berita/selamat-datang');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('seo.description')
            ->has('seo.canonical')
        );

    if (! app(HttpGateway::class)->isHealthy()) {
        return;
    }

    $html = $response->getContent();
    expect($html)->toMatch('/<meta[^>]+name="description"/')
        ->and($html)->toMatch('/<link[^>]+rel="canonical"/');
});

it('OG tags ada', function () {
    $response = $this->get('/berita/selamat-datang');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('seo.ogTitle')
            ->where('seo.ogType', 'article')
        );

    if (! app(HttpGateway::class)->isHealthy()) {
        return;
    }

    $html = $response->getContent();
    expect($html)->toMatch('/property="og:title"/')
        ->and($html)->toMatch('/property="og:type"/');
});

it('Komponen JsonLd meng-escape karakter HTML setelah stringify', function () {
    $src = file_get_contents(resource_path('js/components/seo/json-ld.tsx'));

    expect($src)->toContain('\\u003c')
        ->and($src)->toContain('\\u003e')
        ->and($src)->toContain('\\u0026')
        ->and($src)->toMatch('/replace\s*\(\s*\/<\//');
});
