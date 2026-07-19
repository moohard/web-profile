<?php

use App\Models\Language;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
});

it('GET / → 200 home', function () {
    $this->get('/')->assertOk();
});

it('GET /en/ → 200 home locale EN', function () {
    $this->get('/en')->assertOk();
    expect(app()->getLocale())->toBe('en');
});

it('GET /berita → archive', function () {
    $this->get('/berita')->assertOk();
});

it('GET /en/news → archive (EN) — slug type tidak diterjemahkan', function () {
    // Catatan: di Fase 4, content_type slug TIDAK diterjemahkan (slug adalah `berita` di semua locale).
    // Maka /en/news tidak resolve; /en/berita yang resolve.
    $this->get('/en/news')->assertNotFound();
    $this->get('/en/berita')->assertOk();
});

it('GET /berita/selamat-datang → single post', function () {
    $this->get('/berita/selamat-datang')->assertOk();
});

it('GET /en/berita/welcome → single post EN (slug translation EN)', function () {
    // slug post diterjemahkan (selamat-datang → welcome), tapi slug type tidak.
    $this->get('/en/berita/welcome')->assertOk();
});

it('GET /nonexistent-slug → 404', function () {
    $this->get('/aaaaaa')->assertNotFound();
});

it('GET /admin redirect ke login (bukan publik)', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('beranda menyediakan url single post yang dapat diklik', function () {
    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->component('public/home')
        ->has('latestPosts.0.url')
        ->where('latestPosts.0.url', '/berita/selamat-datang')
    );
});

it('arsip menyediakan url single post yang dapat diklik', function () {
    $this->get('/berita')->assertInertia(fn (Assert $page) => $page
        ->component('public/post-archive')
        ->where('posts.data.0.url', '/berita/selamat-datang')
    );
});
