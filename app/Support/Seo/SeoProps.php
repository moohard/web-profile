<?php

declare(strict_types=1);

namespace App\Support\Seo;

/**
 * Builder props SEO untuk halaman publik (meta, canonical, hreflang, OG).
 */
class SeoProps
{
    /**
     * @param  array<string, string>  $hreflang  locale → absolute URL
     * @return array{
     *     title: string,
     *     description: ?string,
     *     canonical: string,
     *     hreflang: array<string, string>,
     *     ogType: string,
     *     ogImage: ?string,
     *     ogTitle: string,
     *     ogDescription: ?string
     * }
     */
    public static function for(
        string $title,
        ?string $description,
        string $canonical,
        array $hreflang = [],
        string $ogType = 'website',
        ?string $ogImage = null,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'hreflang' => $hreflang,
            'ogType' => $ogType,
            'ogImage' => $ogImage,
            'ogTitle' => $title,
            'ogDescription' => $description,
        ];
    }
}
