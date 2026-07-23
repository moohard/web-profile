<?php

declare(strict_types=1);

namespace App\Support\Posts;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostFeaturedImage
{
    /**
     * @return null|array{id: int, url: string, src: string, srcset: string, thumb_url: string, alt: string}
     */
    public static function from(?Media $media): ?array
    {
        if ($media === null) {
            return null;
        }

        $conversion = $media->hasGeneratedConversion('webp_large')
            ? 'webp_large'
            : '';
        $srcset = $media->getSrcset($conversion);

        if ($srcset === '' && $conversion !== '') {
            $srcset = $media->getSrcset();
        }

        $src = $media->getUrl($conversion);

        return [
            'id' => $media->id,
            'url' => $src,
            'src' => $src,
            'srcset' => $srcset,
            'thumb_url' => $media->hasGeneratedConversion('thumb')
                ? $media->getUrl('thumb')
                : $media->getUrl(),
            'alt' => (string) ($media->getCustomProperty('alt') ?? ''),
        ];
    }
}
