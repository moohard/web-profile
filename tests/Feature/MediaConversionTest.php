<?php

declare(strict_types=1);

use App\Models\ContentType;
use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('Upload JPEG ke Post featured — conversion webp tersedia', function () {
    $type = ContentType::factory()->create(['slug' => 'berita']);
    $post = Post::factory()->create(['type_id' => $type->id]);

    $post->addMedia(UploadedFile::fake()->image('test.jpg', 2000, 2000))
        ->toMediaCollection('featured');

    // Proses job konversi queued (driver sync menjalankan langsung; worker untuk safety)
    $this->artisan('queue:work', ['--stop-when-empty' => true, '--max-jobs' => 10]);

    $media = $post->fresh()->getFirstMedia('featured');

    expect($media)->not->toBeNull()
        ->and($media->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($media->hasGeneratedConversion('webp_small'))->toBeTrue()
        ->and($media->hasGeneratedConversion('webp_medium'))->toBeTrue()
        ->and($media->hasGeneratedConversion('webp_large'))->toBeTrue();
});

it('Upload SVG — tidak ada conversion, file asli tersimpan', function () {
    $type = ContentType::factory()->create(['slug' => 'berita']);
    $post = Post::factory()->create(['type_id' => $type->id]);

    $svgContent = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect width="10" height="10"/></svg>';
    $svgPath = sys_get_temp_dir().'/media-test-'.uniqid('', true).'.svg';
    file_put_contents($svgPath, $svgContent);

    $post->addMedia($svgPath)
        ->usingFileName('icon.svg')
        ->toMediaCollection('featured');

    $media = $post->fresh()->getFirstMedia('featured');

    expect($media)->not->toBeNull()
        ->and($media->mime_type)->toBe('image/svg+xml')
        ->and($media->hasGeneratedConversion('thumb'))->toBeFalse()
        ->and($media->hasGeneratedConversion('webp_medium'))->toBeFalse();

    @unlink($svgPath);
});

it('Soft delete Post — media TETAP ada (baru terhapus saat forceDelete)', function () {
    $type = ContentType::factory()->create(['slug' => 'berita']);
    $post = Post::factory()->create(['type_id' => $type->id]);

    $media = $post->addMedia(UploadedFile::fake()->image('x.jpg', 100, 100))
        ->toMediaCollection('featured');

    $mediaId = $media->id;
    $path = $media->getPath();

    $post->delete();

    expect($post->trashed())->toBeTrue()
        ->and(Media::find($mediaId))->not->toBeNull()
        ->and(file_exists($path))->toBeTrue();
});

it('forceDelete Post — media terhapus permanen', function () {
    $type = ContentType::factory()->create(['slug' => 'berita']);
    $post = Post::factory()->create(['type_id' => $type->id]);

    $media = $post->addMedia(UploadedFile::fake()->image('x.jpg', 100, 100))
        ->toMediaCollection('featured');

    $mediaId = $media->id;
    $path = $media->getPath();

    $post->forceDelete();

    expect(Media::find($mediaId))->toBeNull()
        ->and(file_exists($path))->toBeFalse();
});
