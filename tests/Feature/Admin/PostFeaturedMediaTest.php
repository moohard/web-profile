<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\ContentType;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('email', config('admin.email'))->firstOrFail();
    $this->languageId = Language::idFor('id');
    $this->contentType = ContentType::query()->firstOrFail();
});

it('menghapus kolom URL featured_image dari posts', function () {
    expect(Schema::hasColumn('posts', 'featured_image'))->toBeFalse();
});

it('membuat Post dengan menyalin media terpilih ke koleksi featured', function () {
    $sourcePost = Post::factory()->create(['type_id' => $this->contentType->id]);
    $sourceMedia = $sourcePost
        ->addMedia(UploadedFile::fake()->image('source.jpg', 1200, 800))
        ->withCustomProperties(['alt' => 'Pemandangan kantor'])
        ->toMediaCollection('library');

    $this->actingAs($this->admin)->post('/admin/posts', [
        'type_id' => $this->contentType->id,
        'featured_media_id' => $sourceMedia->id,
        'translations' => [[
            'language_id' => $this->languageId,
            'title' => 'Post dengan media',
            'status' => PostStatus::Draft->value,
        ]],
    ])->assertRedirect(route('admin.posts.index'));

    $post = Post::query()
        ->whereHas('translations', fn ($query) => $query->where('title', 'Post dengan media'))
        ->firstOrFail();
    $featured = $post->getFirstMedia('featured');

    expect($featured)->not->toBeNull()
        ->and($featured?->id)->not->toBe($sourceMedia->id)
        ->and($featured?->getCustomProperty('alt'))->toBe('Pemandangan kantor')
        ->and(Media::query()->whereKey($sourceMedia->id)->exists())->toBeTrue()
        ->and($sourcePost->fresh()->getFirstMedia('library')?->id)->toBe($sourceMedia->id);
});

it('update featured bersifat single-file, idempotent, dan dapat dibersihkan', function () {
    $post = Post::factory()
        ->withTranslation('id', $this->languageId, ['title' => 'Media lama'])
        ->create(['type_id' => $this->contentType->id]);
    $sourcePost = Post::factory()->create(['type_id' => $this->contentType->id]);
    $firstSource = $sourcePost->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection('library');
    $secondSource = $sourcePost->addMedia(UploadedFile::fake()->image('second.jpg'))->toMediaCollection('library');

    $payload = [
        'type_id' => $this->contentType->id,
        'featured_media_id' => $firstSource->id,
        'translations' => [[
            'language_id' => $this->languageId,
            'title' => 'Media baru',
            'status' => PostStatus::Draft->value,
        ]],
    ];

    $this->actingAs($this->admin)->put("/admin/posts/{$post->id}", $payload)->assertRedirect();
    $firstFeaturedId = $post->fresh()->getFirstMedia('featured')?->id;

    $payload['featured_media_id'] = $firstFeaturedId;
    $this->actingAs($this->admin)->put("/admin/posts/{$post->id}", $payload)->assertRedirect();

    expect($post->fresh()->getMedia('featured'))->toHaveCount(1)
        ->and($post->fresh()->getFirstMedia('featured')?->id)->toBe($firstFeaturedId);

    $payload['featured_media_id'] = $secondSource->id;
    $this->actingAs($this->admin)->put("/admin/posts/{$post->id}", $payload)->assertRedirect();

    expect($post->fresh()->getMedia('featured'))->toHaveCount(1)
        ->and($post->fresh()->getFirstMedia('featured')?->id)->not->toBe($firstFeaturedId);

    $payload['featured_media_id'] = null;
    $this->actingAs($this->admin)->put("/admin/posts/{$post->id}", $payload)->assertRedirect();

    expect($post->fresh()->getMedia('featured'))->toHaveCount(0);
});

it('menolak featured_media_id yang bukan image', function () {
    $sourcePost = Post::factory()->create(['type_id' => $this->contentType->id]);
    $document = $sourcePost
        ->addMedia(UploadedFile::fake()->create('document.txt', 1, 'text/plain'))
        ->toMediaCollection('library');

    $this->actingAs($this->admin)->post('/admin/posts', [
        'type_id' => $this->contentType->id,
        'featured_media_id' => $document->id,
        'translations' => [[
            'language_id' => $this->languageId,
            'title' => 'Media invalid',
            'status' => PostStatus::Draft->value,
        ]],
    ])->assertSessionHasErrors('featured_media_id');
});

it('form edit mengirim kontrak featured_media', function () {
    $post = Post::factory()
        ->withTranslation('id', $this->languageId)
        ->create(['type_id' => $this->contentType->id]);
    $media = $post
        ->addMedia(UploadedFile::fake()->image('featured.jpg'))
        ->withCustomProperties(['alt' => 'Alt featured'])
        ->toMediaCollection('featured');

    $this->actingAs($this->admin)
        ->get("/admin/posts/{$post->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('post.featured_media.id', $media->id)
            ->where('post.featured_media.alt', 'Alt featured')
            ->has('post.featured_media.url')
            ->has('post.featured_media.thumb_url')
            ->missing('post.featured_image')
        );
});
