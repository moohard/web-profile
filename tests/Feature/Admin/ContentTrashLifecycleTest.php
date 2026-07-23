<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    $this->seed();
    Storage::fake('public');
});

it('soft delete Post mempertahankan translation dan media di Trash', function (): void {
    $languageId = Language::idFor('id');
    $contentType = ContentType::query()->firstOrFail();
    $post = Post::factory()
        ->withTranslation('id', $languageId, ['title' => 'Masuk Trash'])
        ->create(['type_id' => $contentType->id]);
    $media = $post
        ->addMedia(UploadedFile::fake()->image('featured.jpg'))
        ->toMediaCollection('featured_image');

    $post->delete();

    expect(Post::query()->find($post->id))->toBeNull()
        ->and(Post::withTrashed()->findOrFail($post->id)->trashed())->toBeTrue()
        ->and($post->translations()->where('title', 'Masuk Trash')->exists())->toBeTrue()
        ->and(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});

it('soft delete Page mempertahankan translation dan media di Trash', function (): void {
    $languageId = Language::idFor('id');
    $page = Page::factory()->create();
    $translation = PageTranslation::factory()
        ->for($page)
        ->create(['language_id' => $languageId, 'title' => 'Halaman Trash']);
    $media = $page
        ->addMedia(UploadedFile::fake()->image('hero.jpg'))
        ->toMediaCollection('hero_image');

    $page->delete();

    expect(Page::query()->find($page->id))->toBeNull()
        ->and(Page::withTrashed()->findOrFail($page->id)->trashed())->toBeTrue()
        ->and(PageTranslation::query()->whereKey($translation->id)->exists())->toBeTrue()
        ->and(Media::query()->whereKey($media->id)->exists())->toBeTrue();
});
