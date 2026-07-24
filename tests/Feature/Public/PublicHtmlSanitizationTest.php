<?php

use App\Enums\PageMode;
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

it('mengirim templateKey registry dan fallback default untuk key tidak dikenal', function () {
    $landing = Page::factory()->create([
        'mode' => PageMode::Template,
        'template_key' => 'landing',
    ]);
    PageTranslation::factory()->for($landing)->create([
        'language_id' => $this->langId,
        'slug' => 'landing-page',
        'status' => 'Published',
    ]);

    $this->get('/landing-page')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('templateKey', 'landing')
        );

    $unknown = Page::factory()->create([
        'mode' => PageMode::Template,
        'template_key' => 'uploaded-template',
    ]);
    PageTranslation::factory()->for($unknown)->create([
        'language_id' => $this->langId,
        'slug' => 'unknown-template',
        'status' => 'Published',
    ]);

    $this->get('/unknown-template')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('templateKey', 'default')
        );
});
