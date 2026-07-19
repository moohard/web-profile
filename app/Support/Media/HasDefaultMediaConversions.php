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

        // Metode Conversion-native (nonQueued/queued/withResponsiveImages) dipanggil
        // lebih dulu, baru manipulasi gambar (fit/format/quality) dari image driver.
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->fit(Fit::Max, 400, 400)
            ->format('webp')
            ->quality(80);

        $this->addMediaConversion('webp_small')
            ->queued()
            ->fit(Fit::Max, 480, 480)
            ->format('webp')
            ->quality(80);

        $this->addMediaConversion('webp_medium')
            ->queued()
            ->fit(Fit::Max, 960, 960)
            ->format('webp')
            ->quality(80);

        $this->addMediaConversion('webp_large')
            ->queued()
            ->withResponsiveImages()
            ->fit(Fit::Max, 1920, 1920)
            ->format('webp')
            ->quality(80);
    }
}
