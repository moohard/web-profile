<?php

declare(strict_types=1);

namespace App\Actions\Galleries;

use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Support\Facades\DB;

class SyncGalleryImages
{
    /**
     * @param  list<array{id?: int|string|null, path: string, captions: list<array{language_id: int, caption: ?string}>}>  $images
     */
    public function __invoke(Gallery $gallery, array $images): void
    {
        DB::transaction(function () use ($gallery, $images): void {
            $lockedGallery = Gallery::query()->lockForUpdate()->findOrFail($gallery->id);
            $existingImages = $lockedGallery->images()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $imageIds = collect($images)
                ->pluck('id')
                ->filter(fn (mixed $id): bool => is_numeric($id))
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $lockedGallery->images()->whereNotIn('id', $imageIds)->delete();

            foreach ($images as $sortOrder => $imageData) {
                $imageId = $imageData['id'] ?? null;
                $image = is_numeric($imageId) ? $existingImages->get((int) $imageId) : null;

                if (! $image instanceof GalleryImage) {
                    $image = $lockedGallery->images()->create([
                        'path' => $imageData['path'],
                        'sort_order' => $sortOrder,
                    ]);
                } else {
                    $image->update([
                        'path' => $imageData['path'],
                        'sort_order' => $sortOrder,
                    ]);
                }

                foreach ($imageData['captions'] as $caption) {
                    $image->translations()->updateOrCreate(
                        ['language_id' => $caption['language_id']],
                        ['caption' => $caption['caption']],
                    );
                }
            }
        }, attempts: 3);
    }
}
