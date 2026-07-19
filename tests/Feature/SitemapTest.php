<?php

use App\Models\Language;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

it('sitemap:generate membuat sitemap.xml berisi URL post demo', function () {
    $this->artisan('sitemap:generate')->assertSuccessful();

    $xml = file_get_contents(public_path('sitemap.xml'));

    expect($xml)->toContain('<urlset')
        ->and($xml)->toContain('/berita/selamat-datang')
        ->and($xml)->toContain('/en/berita/welcome')
        ->and($xml)->toContain(url('/'));
});

it('GET /sitemap.xml returns 200 dengan XML', function () {
    $this->artisan('sitemap:generate');

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->getContent())->toContain('<urlset');
});
