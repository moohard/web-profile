<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

it('Arsip /berita mengirim props SEO (title/canonical/hreflang ID+EN+x-default)', function () {
    $this->get('/berita')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-archive')
            ->has('seo.title')
            ->has('seo.canonical')
            ->has('seo.hreflang.id')
            ->has('seo.hreflang.en')
            ->has('seo.hreflang.x-default')
            ->where('seo.ogType', 'website')
        );
});

it('Custom page mengirim props SEO dengan hreflang ID+EN dan x-default = bahasa default', function () {
    $idLang = Language::idFor('id');
    $enLang = Language::idFor('en');

    $page = Page::create([
        'mode' => 'Template',
        'hero_enabled' => false,
        'sidebar_enabled' => false,
    ]);

    PageTranslation::create([
        'page_id' => $page->id,
        'language_id' => $idLang,
        'slug' => 'profil',
        'title' => 'Profil',
        'status' => 'Published',
        'meta_description' => 'Halaman profil organisasi.',
    ]);

    PageTranslation::create([
        'page_id' => $page->id,
        'language_id' => $enLang,
        'slug' => 'profile',
        'title' => 'Profile',
        'status' => 'Published',
    ]);

    $this->get('/profil')
        ->assertOk()
        ->assertInertia(fn (Assert $inertia) => $inertia
            ->component('public/page-show')
            ->has('seo.title')
            ->has('seo.canonical')
            ->has('seo.description')
            ->has('seo.hreflang.id')
            ->has('seo.hreflang.en')
            // x-default menunjuk URL bahasa default (id → '/profil'), bukan sekadar entri pertama array.
            ->where('seo.hreflang.x-default', fn (string $url): bool => str_ends_with($url, '/profil'))
        );
});
