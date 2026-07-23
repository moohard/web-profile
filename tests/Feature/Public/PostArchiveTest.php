<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    Language::flushCache();
    $this->idLang = Language::idFor('id');
    // 'pengumuman' sengaja dipakai (bukan 'berita') — DemoPostSeeder mengisi 1 post
    // published ke 'berita', yang akan mengacaukan assertion count/order di bawah.
    $this->type = ContentType::query()->where('slug', 'pengumuman')->firstOrFail();
});

it('arsip hanya menampilkan post Published pada locale aktif, urut published_at terbaru dulu', function () {
    Post::factory()
        ->withTranslation('id', $this->idLang, ['title' => 'Lama', 'published_at' => now()->subDays(5)])
        ->create(['type_id' => $this->type->id]);
    Post::factory()
        ->withTranslation('id', $this->idLang, ['title' => 'Baru', 'published_at' => now()->subDay()])
        ->create(['type_id' => $this->type->id]);
    Post::factory()
        ->withTranslation('id', $this->idLang, ['title' => 'Draft', 'status' => PostStatus::Draft])
        ->create(['type_id' => $this->type->id]);

    $this->get('/pengumuman')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-archive')
            ->has('posts.data', 2) // Draft tidak ikut
            ->where('posts.data.0.title', 'Baru')
            ->where('posts.data.1.title', 'Lama')
        );
});

it('post yang di-trash tetap tidak muncul di arsip (regresi D1)', function () {
    $post = Post::factory()
        ->withTranslation('id', $this->idLang, ['title' => 'Bakal Trash'])
        ->create(['type_id' => $this->type->id]);
    $post->delete();

    $this->get('/pengumuman')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-archive')
            ->has('posts.data', 0)
        );
});

it('item arsip menyertakan excerpt dan published_at terformat', function () {
    Post::factory()
        ->withTranslation('id', $this->idLang, [
            'title' => 'Punya Excerpt',
            'body' => '<p>'.str_repeat('kata ', 60).'</p>',
        ])
        ->create(['type_id' => $this->type->id]);

    $this->get('/pengumuman')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('posts.data.0.excerpt')
            ->has('posts.data.0.published_at')
            ->where('posts.data.0.excerpt', fn (string $excerpt): bool => ! str_contains($excerpt, '<p>'))
        );
});

it('item arsip TANPA featured media → featured null (tidak error)', function () {
    Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Tanpa Gambar'])
        ->create(['type_id' => $this->type->id]);

    $this->get('/pengumuman')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('posts.data.0.featured', null)
        );
});

it('item arsip DENGAN featured media → featured.src terisi dari konversi webp_large', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang, ['title' => 'Ada Gambar'])
        ->create(['type_id' => $this->type->id]);
    $post->addMedia(UploadedFile::fake()->image('archive.jpg', 1200, 800))->toMediaCollection('featured');

    $this->get('/pengumuman')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where(
                'posts.data.0.featured.src',
                fn (string $url): bool => str_contains($url, 'webp_large'),
            )
        );
});

it('arsip membatasi 12 post per halaman dan mengirim metadata pagination', function () {
    // Loop (bukan count()->withTranslation()) — withTranslation() membangun state
    // slug faker saat dipanggil, jadi count(n) akan mengulang slug yang SAMA di semua n baris.
    foreach (range(1, 15) as $i) {
        Post::factory()
            ->withTranslation('id', $this->idLang, ['title' => "Post {$i}"])
            ->create(['type_id' => $this->type->id]);
    }

    $this->get('/pengumuman')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/post-archive')
            ->has('posts.data', 12)
            ->where('posts.current_page', 1)
            ->where('posts.last_page', 2)
            ->has('posts.links')
        );

    $this->get('/pengumuman?page=2')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('posts.data', 3));
});
