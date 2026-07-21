<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\PostTranslation;

beforeEach(function () {
    $this->seed();
    Language::flushCache();
    $this->idLang = Language::idFor('id');
    $this->type = ContentType::query()->where('slug', 'berita')->firstOrFail();
});

it('Post yang di-soft-delete hilang dari single & arsip publik', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang, ['slug' => 'akan-dihapus', 'title' => 'Akan Dihapus'])
        ->create(['type_id' => $this->type->id]);

    $this->get('/berita/akan-dihapus')->assertOk();

    $post->delete();

    $this->get('/berita/akan-dihapus')->assertNotFound();
    $this->get('/berita')->assertOk();

    $archivedSlugs = PostTranslation::query()
        ->where('language_id', $this->idLang)
        ->whereHas('post', fn ($q) => $q->where('type_id', $this->type->id))
        ->published()
        ->pluck('slug');

    expect($archivedSlugs)->not->toContain('akan-dihapus');
});

it('Beranda tidak menyertakan post yang sudah di-trash pada latestPosts (dan tidak error)', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Berita Trash'])
        ->create(['type_id' => $this->type->id]);
    $post->delete();

    // Sebelum perbaikan (whereHas('post') hilang), query ini menyertakan translation dari
    // post yang sudah trashed lalu error saat mengakses relasi post→type yang null.
    $titles = PostTranslation::query()
        ->with('post.type')
        ->where('language_id', $this->idLang)
        ->whereHas('post')
        ->published()
        ->orderByDesc('published_at')
        ->limit(5)
        ->get()
        ->pluck('title');

    expect($titles)->not->toContain('Berita Trash');

    $this->get('/')->assertOk();
});

it('Halaman yang di-soft-delete hilang dari akses publik (bukan error 500)', function () {
    $page = Page::factory()->create();
    PageTranslation::factory()->for($page)->create([
        'language_id' => $this->idLang,
        'slug' => 'halaman-akan-dihapus',
        'status' => 'Published',
    ]);

    $this->get('/halaman-akan-dihapus')->assertOk();

    $page->delete();

    $this->get('/halaman-akan-dihapus')->assertNotFound();
});
