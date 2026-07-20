<?php

use App\Enums\PostStatus;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\PostTranslation;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
    $this->langId = Language::idFor('id');
});

it('membersihkan HTML berbahaya pada body post sebelum dikirim ke frontend', function () {
    $type = ContentType::where('slug', 'berita')->firstOrFail();
    $post = Post::factory()->create(['type_id' => $type->id]);
    PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => $this->langId,
        'slug' => 'xss-post',
        'title' => 'XSS Post',
        'body' => '<script>alert(1)</script><p>Halo dunia</p>',
        'status' => PostStatus::Published,
        'published_at' => now(),
    ]);

    $this->get('/berita/xss-post')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-show')
            ->where('post.body', '<p>Halo dunia</p>')
        );
});

it('membersihkan HTML berbahaya pada content halaman sebelum dikirim ke frontend', function () {
    $page = Page::factory()->create();
    PageTranslation::create([
        'page_id' => $page->id,
        'language_id' => $this->langId,
        'slug' => 'xss-page',
        'title' => 'XSS Page',
        'content' => ['html' => '<p onclick="evil()">Isi <script>x</script>halaman</p>'],
        'status' => 'Published',
    ]);

    $this->get('/xss-page')
        ->assertOk()
        ->assertInertia(fn (Assert $inertiaPage) => $inertiaPage
            ->component('public/page-show')
            ->where('page.content.html', '<p>Isi halaman</p>')
        );
});

// Tag `<div class="...">` sengaja dipakai sebagai pembeda profil: allowlist
// `cms_page` mengizinkan `div[class]`, sedangkan `default` (rich-text) tidak
// mengizinkan `div` sama sekali (tag dibuang, teks di dalamnya tetap ada).

it('body post dirender publik dengan profil rich-text (default) — tag di luar profil dibuang', function () {
    $type = ContentType::where('slug', 'berita')->firstOrFail();
    $post = Post::factory()->create(['type_id' => $type->id]);
    PostTranslation::create([
        'post_id' => $post->id,
        'language_id' => $this->langId,
        'slug' => 'rich-text-post',
        'title' => 'Rich Text Post',
        'body' => '<div class="callout">Penting</div><h2>Judul</h2>',
        'status' => PostStatus::Published,
        'published_at' => now(),
    ]);

    $this->get('/berita/rich-text-post')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-show')
            ->where('post.body', 'Penting<h2>Judul</h2>')
        );
});

it('konten Page mode Template dirender publik dengan profil rich-text (default)', function () {
    $page = Page::factory()->create(['mode' => 'Template']);
    PageTranslation::create([
        'page_id' => $page->id,
        'language_id' => $this->langId,
        'slug' => 'rich-text-template-page',
        'title' => 'Rich Text Template Page',
        'content' => ['html' => '<div class="callout">Penting</div><h2>Judul</h2>'],
        'status' => 'Published',
    ]);

    $this->get('/rich-text-template-page')
        ->assertOk()
        ->assertInertia(fn (Assert $inertiaPage) => $inertiaPage
            ->component('public/page-show')
            ->where('page.content.html', 'Penting<h2>Judul</h2>')
        );
});

it('konten Page mode Code tetap dirender publik dengan profil cms_page (regresi)', function () {
    $page = Page::factory()->create(['mode' => 'Code']);
    PageTranslation::create([
        'page_id' => $page->id,
        'language_id' => $this->langId,
        'slug' => 'cms-page-code-page',
        'title' => 'Code Page',
        'content' => ['html' => '<div class="callout">Penting</div><h2>Judul</h2>'],
        'status' => 'Published',
    ]);

    $this->get('/cms-page-code-page')
        ->assertOk()
        ->assertInertia(fn (Assert $inertiaPage) => $inertiaPage
            ->component('public/page-show')
            ->where('page.content.html', '<div class="callout">Penting</div><h2>Judul</h2>')
        );
});
