<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Models\Language;
use Throwable;

/**
 * Builder props SEO untuk halaman publik (meta, canonical, hreflang, OG).
 */
class SeoProps
{
    /**
     * Tambahkan entri `x-default` yang menunjuk ke URL bahasa default
     * (bukan sekadar entri pertama pada array). Bila default tak ada di map,
     * fallback ke entri pertama; bila map kosong, kembalikan apa adanya.
     *
     * @param  array<string, string>  $hreflang  locale → absolute URL
     * @return array<string, string>
     */
    public static function withXDefault(array $hreflang): array
    {
        if ($hreflang === []) {
            return $hreflang;
        }

        try {
            $default = Language::defaultModel()->code;
        } catch (Throwable) {
            $default = (string) config('app.locale', 'id');
        }

        $hreflang['x-default'] = $hreflang[$default] ?? reset($hreflang);

        return $hreflang;
    }

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
