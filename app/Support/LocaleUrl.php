<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Language;
use Illuminate\Support\Collection;

class LocaleUrl
{
    /** Ambil daftar locale aktif (code). */
    public static function active(): Collection
    {
        return Language::active()->pluck('code');
    }

    /** Apakah code adalah locale valid non-default? */
    public static function isNonDefaultLocale(string $code): bool
    {
        $default = Language::defaultModel()->code;

        return $code !== $default && static::active()->contains($code);
    }

    /** Bangun URL untuk locale tertentu dari path yang sudah tanpa-prefix-locale. */
    public static function for(string $locale, string $pathWithoutLocale): string
    {
        $pathWithoutLocale = '/'.ltrim($pathWithoutLocale, '/');
        $default = Language::defaultModel()->code;

        if ($locale === $default) {
            return $pathWithoutLocale === '/' ? '/' : $pathWithoutLocale;
        }

        return '/'.$locale.($pathWithoutLocale === '/' ? '' : $pathWithoutLocale);
    }

    /** Locale aktif sesuai app() saat ini. */
    public static function current(): string
    {
        return app()->getLocale();
    }
}
