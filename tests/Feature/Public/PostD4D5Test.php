<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
    $this->languageId = Language::idFor('id');
    $this->contentType = ContentType::factory()
        ->withTranslation('id', $this->languageId, [
            'name' => 'Artikel D4',
            'description' => 'Arsip artikel D4',
        ])
        ->create(['slug' => 'artikel-d4']);
    $this->category = Category::factory()
        ->withTranslation('id', $this->languageId, ['name' => 'Teknologi'])
        ->create();
    $this->tag = Tag::factory()
        ->withTranslation('id', $this->languageId, ['name' => 'Laravel'])
        ->create();
});

function createD4Post(object $test, array $translation = []): Post
{
    $post = Post::factory()
        ->withTranslation('id', $test->languageId, array_merge([
            'slug' => 'post-d4',
            'title' => 'Post D4',
            'body' => '<p>Isi artikel yang cukup panjang untuk menjadi fallback excerpt publik.</p>',
            'status' => PostStatus::Published,
            'published_at' => now()->subMinute(),
            'meta_description' => null,
        ], $translation))
        ->create([
            'type_id' => $test->contentType->id,
            'category_id' => $test->category->id,
        ]);
    $post->tags()->attach($test->tag);
    $post->addMedia(UploadedFile::fake()->image('featured.jpg', 1600, 900))
        ->withCustomProperties(['alt' => 'Gambar artikel'])
        ->toMediaCollection('featured');

    return $post;
}

it('archive mengirim card payload lengkap dan metadata paginator', function () {
    createD4Post($this);

    $this->get('/artikel-d4')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-archive')
            ->where('posts.data.0.title', 'Post D4')
            ->where('posts.data.0.url', '/artikel-d4/post-d4')
            ->where('posts.data.0.excerpt', 'Isi artikel yang cukup panjang untuk menjadi fallback excerpt publik.')
            ->where('posts.data.0.category.name', 'Teknologi')
            ->has('posts.data.0.published_at')
            ->has('posts.data.0.featured.src')
            ->has('posts.data.0.featured.srcset')
            ->where('posts.data.0.featured.alt', 'Gambar artikel')
            ->where('posts.current_page', 1)
            ->where('posts.last_page', 1)
            ->has('posts.links')
        );
});

it('archive memakai excerpt post pertama ketika deskripsi content type kosong', function () {
    $this->contentType->translations()
        ->where('language_id', $this->languageId)
        ->update(['description' => null]);

    createD4Post($this);

    $this->get('/artikel-d4')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.description', 'Isi artikel yang cukup panjang untuk menjadi fallback excerpt publik.')
            ->where('seo.ogDescription', 'Isi artikel yang cukup panjang untuk menjadi fallback excerpt publik.')
        );
});

it('archive mempertahankan page query dan membatasi dua belas item', function () {
    foreach (range(1, 13) as $index) {
        Post::factory()
            ->withTranslation('id', $this->languageId, [
                'slug' => "post-{$index}",
                'title' => "Post {$index}",
                'status' => PostStatus::Published,
                'published_at' => now()->subMinutes($index),
            ])
            ->create(['type_id' => $this->contentType->id]);
    }

    $this->get('/artikel-d4?page=2')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('posts.current_page', 2)
            ->where('posts.last_page', 2)
            ->has('posts.data', 1)
            ->where('posts.prev_page_url', fn (?string $url): bool => $url !== null && str_contains($url, '/artikel-d4?page=1'))
        );
});

it('single mengirim media, tanggal, kategori, tags, body, JSON-LD, dan fallback SEO excerpt', function () {
    createD4Post($this);

    $this->get('/artikel-d4/post-d4')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-show')
            ->where('post.title', 'Post D4')
            ->where('post.category.name', 'Teknologi')
            ->where('post.tags.0.name', 'Laravel')
            ->where('post.featured.alt', 'Gambar artikel')
            ->has('post.featured.src')
            ->has('post.featured.srcset')
            ->has('post.published_at')
            ->where('post.body', '<p>Isi artikel yang cukup panjang untuk menjadi fallback excerpt publik.</p>')
            ->where('seo.description', 'Isi artikel yang cukup panjang untuk menjadi fallback excerpt publik.')
            ->where('seo.ogDescription', 'Isi artikel yang cukup panjang untuk menjadi fallback excerpt publik.')
            ->where('jsonLd.image.0', fn (string $url): bool => str_contains($url, 'featured'))
            ->where('jsonLd.articleSection', 'Teknologi')
            ->where('jsonLd.keywords.0', 'Laravel')
        );
});

it('archive kosong tetap mengirim paginator kosong', function () {
    $this->get('/artikel-d4')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data', 0)
            ->where('posts.current_page', 1)
            ->where('posts.last_page', 1)
        );
});
