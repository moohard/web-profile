<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;

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

it('sitemap:generate tidak menyertakan halaman yang sudah di-trash', function () {
    $langId = Language::idFor('id');
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create([
        'language_id' => $langId,
        'slug' => 'halaman-trash-sitemap',
        'status' => 'Published',
    ]);
    $page->delete();

    $this->artisan('sitemap:generate')->assertSuccessful();

    $xml = file_get_contents(public_path('sitemap.xml'));

    expect($xml)->not->toContain('halaman-trash-sitemap');
});
