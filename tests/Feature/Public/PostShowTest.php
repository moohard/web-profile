<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Language::flushCache();
    $this->idLang = Language::idFor('id');
    $this->type = ContentType::query()->where('slug', 'berita')->firstOrFail();
});

it('single menampilkan kategori dan tag post', function () {
    $category = Category::factory()->withTranslation('id', $this->idLang, ['name' => 'Teknologi'])->create();
    $tag = Tag::factory()->withTranslation('id', $this->idLang, ['name' => 'AI'])->create();

    $post = Post::factory()
        ->withTranslation('id', $this->idLang, ['slug' => 'ada-kategori'])
        ->create(['type_id' => $this->type->id, 'category_id' => $category->id]);
    $post->tags()->attach($tag->id);

    $this->get('/berita/ada-kategori')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-show')
            ->where('category.name', 'Teknologi')
            ->where('tags.0.name', 'AI')
        );
});

it('single TANPA kategori/tag → category null dan tags kosong (tidak error)', function () {
    Post::factory()
        ->withTranslation('id', $this->idLang, ['slug' => 'tanpa-kategori'])
        ->create(['type_id' => $this->type->id]);

    $this->get('/berita/tanpa-kategori')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('category', null)
            ->where('tags', [])
        );
});

it('meta_description fallback ke excerpt(body) saat kosong', function () {
    Post::factory()
        ->withTranslation('id', $this->idLang, [
            'slug' => 'tanpa-meta',
            'body' => '<p>Isi lengkap tanpa meta description sama sekali di sini.</p>',
            'meta_description' => null,
        ])
        ->create(['type_id' => $this->type->id]);

    $this->get('/berita/tanpa-meta')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.description', 'Isi lengkap tanpa meta description sama sekali di sini.')
            ->where('seo.ogDescription', 'Isi lengkap tanpa meta description sama sekali di sini.')
        );
});

it('meta_description eksplisit TIDAK ditimpa fallback excerpt', function () {
    Post::factory()
        ->withTranslation('id', $this->idLang, [
            'slug' => 'ada-meta',
            'body' => '<p>Isi body yang berbeda dari meta description.</p>',
            'meta_description' => 'Deskripsi meta manual.',
        ])
        ->create(['type_id' => $this->type->id]);

    $this->get('/berita/ada-meta')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.description', 'Deskripsi meta manual.')
        );
});

it('gambar OG/JSON-LD diambil dari featured WebP, bukan kolom featured_image', function () {
    $post = Post::factory()
        ->withTranslation('id', $this->idLang, ['slug' => 'ada-gambar'])
        ->create(['type_id' => $this->type->id]);
    $post->addMedia(UploadedFile::fake()->image('og.jpg', 1600, 1000))->toMediaCollection('featured');

    $this->get('/berita/ada-gambar')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where(
                'seo.ogImage',
                fn (?string $url): bool => $url !== null && str_contains($url, 'webp_large'),
            )
            ->where(
                'jsonLd.image.0',
                fn (?string $url): bool => $url !== null && str_contains($url, 'webp_large'),
            )
        );
});

it('single TANPA featured media → ogImage null dan jsonLd.image kosong (tidak error)', function () {
    Post::factory()
        ->withTranslation('id', $this->idLang, ['slug' => 'tanpa-gambar'])
        ->create(['type_id' => $this->type->id]);

    $this->get('/berita/tanpa-gambar')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seo.ogImage', null)
            ->where('jsonLd.image', [])
        );
});
