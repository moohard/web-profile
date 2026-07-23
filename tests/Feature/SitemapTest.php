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

it('sitemap memakai bahasa aktif dinamis dan mengecualikan Draft serta bahasa inactive', function () {
    $french = Language::factory()->create([
        'code' => 'fr',
        'is_active' => true,
        'sort_order' => 3,
    ]);
    $german = Language::factory()->create([
        'code' => 'de',
        'is_active' => false,
        'sort_order' => 4,
    ]);
    $page = Page::factory()->create();

    PageTranslation::factory()->create([
        'page_id' => $page->id,
        'language_id' => $french->id,
        'slug' => 'a-propos',
        'status' => 'Published',
    ]);
    PageTranslation::factory()->create([
        'page_id' => $page->id,
        'language_id' => $german->id,
        'slug' => 'uber-uns',
        'status' => 'Published',
    ]);
    PageTranslation::factory()->create([
        'page_id' => Page::factory()->create()->id,
        'language_id' => $french->id,
        'slug' => 'brouillon',
        'status' => 'Draft',
    ]);
    Language::flushCache();

    $this->artisan('sitemap:generate')->assertSuccessful();
    $xml = file_get_contents(public_path('sitemap.xml'));

    expect($xml)->toContain('/fr/a-propos')
        ->and($xml)->toContain('/fr/berita')
        ->and($xml)->not->toContain('/de')
        ->and($xml)->not->toContain('uber-uns')
        ->and($xml)->not->toContain('brouillon');
});

it('sitemap menghilangkan prefix setelah bahasa aktif menjadi default', function () {
    Language::query()->update(['is_default' => false]);
    Language::query()->where('code', 'en')->update(['is_default' => true]);
    Language::flushCache();

    $this->artisan('sitemap:generate')->assertSuccessful();
    $xml = file_get_contents(public_path('sitemap.xml'));

    expect($xml)->toContain(url('/berita/welcome'))
        ->and($xml)->toContain(url('/id/berita/selamat-datang'))
        ->and($xml)->not->toContain(url('/en/berita/welcome'));
});
