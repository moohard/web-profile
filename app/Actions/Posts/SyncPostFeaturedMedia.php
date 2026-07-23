<?php

declare(strict_types=1);

namespace App\Actions\Posts;

use App\Models\Post;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SyncPostFeaturedMedia
{
    public function __invoke(Post $post, ?int $mediaId): ?Media
    {
        $current = $post->getFirstMedia('featured');

        if ($mediaId === null) {
            $post->clearMediaCollection('featured');

            return null;
        }

        if ($current?->id === $mediaId) {
            return $current;
        }

        $source = Media::query()->findOrFail($mediaId);

        if (! str_starts_with((string) $source->mime_type, 'image/')) {
            throw ValidationException::withMessages([
                'featured_media_id' => 'Media unggulan harus berupa gambar.',
            ]);
        }

        return $source->copy($post, 'featured');
    }
}
