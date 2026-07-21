<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->idLang = Language::idFor('id');
    $this->type = ContentType::query()->firstOrFail();
});

function featuredMediaAdmin(): User
{
    return User::where('email', config('admin.email'))->firstOrFail();
}

it('POST /admin/posts dengan featured_media_id — media di-copy ke koleksi featured post baru', function () {
    $sourcePost = Post::factory()->create(['type_id' => $this->type->id]);
    $sourceMedia = $sourcePost->addMedia(UploadedFile::fake()->image('sumber.jpg', 800, 600))
        ->toMediaCollection('featured');

    $this->actingAs(featuredMediaAdmin())->post('/admin/posts', [
        'type_id' => $this->type->id,
        'featured_media_id' => $sourceMedia->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Punya Gambar',
            'status' => 'Draft',
        ]],
    ])->assertRedirect(route('admin.posts.index'));

    $post = Post::query()->latest('id')->first();
    $featured = $post->fresh()->getFirstMedia('featured');

    expect($featured)->not->toBeNull()
        // di-copy (bukan dipindah) → row media baru, bukan id yang sama
        ->and($featured->id)->not->toBe($sourceMedia->id)
        ->and(Media::find($sourceMedia->id))->not->toBeNull();
});

it('media asal (kepunyaan post lain) tidak ikut berpindah setelah di-copy sebagai featured post baru', function () {
    $sourcePost = Post::factory()->create(['type_id' => $this->type->id]);
    $sourceMedia = $sourcePost->addMedia(UploadedFile::fake()->image('sumber.jpg', 800, 600))
        ->toMediaCollection('featured');

    $this->actingAs(featuredMediaAdmin())->post('/admin/posts', [
        'type_id' => $this->type->id,
        'featured_media_id' => $sourceMedia->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Pinjam Gambar',
            'status' => 'Draft',
        ]],
    ])->assertRedirect();

    expect($sourcePost->fresh()->getFirstMedia('featured')?->id)->toBe($sourceMedia->id);
});

it('PUT update — ganti featured_media_id mengganti isi koleksi singleFile (media lama terhapus)', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);
    $firstMedia = $post->addMedia(UploadedFile::fake()->image('pertama.jpg', 800, 600))->toMediaCollection('featured');

    $otherPost = Post::factory()->create(['type_id' => $this->type->id]);
    $newSourceMedia = $otherPost->addMedia(UploadedFile::fake()->image('baru.jpg', 800, 600))->toMediaCollection('featured');

    $existingTranslation = $post->translations()->first();

    $this->actingAs(featuredMediaAdmin())->put("/admin/posts/{$post->id}", [
        'type_id' => $this->type->id,
        'featured_media_id' => $newSourceMedia->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => $existingTranslation->title,
            'slug' => $existingTranslation->slug,
            'status' => 'Published',
        ]],
    ])->assertRedirect();

    $fresh = $post->fresh();

    expect($fresh->getFirstMedia('featured'))->not->toBeNull()
        ->and($fresh->getFirstMedia('featured')->id)->not->toBe($firstMedia->id)
        ->and(Media::find($firstMedia->id))->toBeNull();
});

it('PUT update dengan featured_media_id SAMA — tidak membuat duplikat media', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);
    $media = $post->addMedia(UploadedFile::fake()->image('tetap.jpg', 800, 600))->toMediaCollection('featured');
    $existingTranslation = $post->translations()->first();

    $countBefore = Media::query()->count();

    $this->actingAs(featuredMediaAdmin())->put("/admin/posts/{$post->id}", [
        'type_id' => $this->type->id,
        'featured_media_id' => $media->id,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => $existingTranslation->title,
            'slug' => $existingTranslation->slug,
            'status' => 'Published',
        ]],
    ])->assertRedirect();

    expect(Media::query()->count())->toBe($countBefore)
        ->and($post->fresh()->getFirstMedia('featured')->id)->toBe($media->id);
});

it('PUT update dengan featured_media_id null — menghapus featured yang sudah ada', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);
    $media = $post->addMedia(UploadedFile::fake()->image('hapus.jpg', 800, 600))->toMediaCollection('featured');
    $existingTranslation = $post->translations()->first();

    $this->actingAs(featuredMediaAdmin())->put("/admin/posts/{$post->id}", [
        'type_id' => $this->type->id,
        'featured_media_id' => null,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => $existingTranslation->title,
            'slug' => $existingTranslation->slug,
            'status' => 'Published',
        ]],
    ])->assertRedirect();

    expect($post->fresh()->getFirstMedia('featured'))->toBeNull()
        ->and(Media::find($media->id))->toBeNull();
});

it('validasi menolak featured_media_id yang tidak ada di tabel media', function () {
    $this->actingAs(featuredMediaAdmin())->post('/admin/posts', [
        'type_id' => $this->type->id,
        'featured_media_id' => 999999,
        'translations' => [[
            'language_id' => $this->idLang,
            'title' => 'Gagal',
            'status' => 'Draft',
        ]],
    ])->assertSessionHasErrors('featured_media_id');
});

it('GET .../edit mengirim featured_media_id dan featured_media_url saat post punya featured', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);
    $media = $post->addMedia(UploadedFile::fake()->image('preview.jpg', 800, 600))->toMediaCollection('featured');

    $this->actingAs(featuredMediaAdmin())
        ->get("/admin/posts/{$post->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/posts/form')
            ->where('post.featured_media_id', $media->id)
            ->has('post.featured_media_url')
        );
});

it('GET .../edit mengirim featured_media_id null saat post tidak punya featured', function () {
    $post = Post::factory()->withTranslation('id', $this->idLang)->create(['type_id' => $this->type->id]);

    $this->actingAs(featuredMediaAdmin())
        ->get("/admin/posts/{$post->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('post.featured_media_id', null)
            ->where('post.featured_media_url', null)
        );
});
