<?php

declare(strict_types=1);

namespace App\Support\Media;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Konversi media default: WebP multi-ukuran + responsive images.
 * SVG dikecualikan (file asli disimpan tanpa konversi).
 */
trait HasDefaultMediaConversions
{
    public function registerMediaConversions(?Media $media = null): void
    {
        // SVG di-skip: tidak ada konversi, file asli disimpan
        if ($media?->mime_type === 'image/svg+xml') {
            return;
        }

        $this->addMediaConversion('thumb')
            ->fit(Fit::Max, 400, 400)
            ->format('webp')
            ->quality(80)
            ->nonQueued();

        $this->addMediaConversion('webp_small')
            ->fit(Fit::Max, 480, 480)
            ->format('webp')
            ->quality(80)
            ->queued();

        $this->addMediaConversion('webp_medium')
            ->fit(Fit::Max, 960, 960)
            ->format('webp')
            ->quality(80)
            ->queued();

        $this->addMediaConversion('webp_large')
            ->fit(Fit::Max, 1920, 1920)
            ->format('webp')
            ->quality(80)
            ->queued()
            ->withResponsiveImages();
    }
}
