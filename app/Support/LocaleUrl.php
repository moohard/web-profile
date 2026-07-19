<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Language;
use Illuminate\Support\Collection;
use Throwable;

class LocaleUrl
{
    /** Ambil daftar locale aktif (code). */
    public static function active(): Collection
    {
        try {
            return Language::active()->pluck('code');
        } catch (Throwable) {
            return collect();
        }
    }

    /** Apakah code adalah locale valid non-default? */
    public static function isNonDefaultLocale(string $code): bool
    {
        try {
            $default = Language::defaultModel()->code;
        } catch (Throwable) {
            return false;
        }

        return $code !== $default && static::active()->contains($code);
    }

    /** Bangun URL untuk locale tertentu dari path yang sudah tanpa-prefix-locale. */
    public static function for(string $locale, string $pathWithoutLocale): string
    {
        $pathWithoutLocale = '/'.ltrim($pathWithoutLocale, '/');

        try {
            $default = Language::defaultModel()->code;
        } catch (Throwable) {
            $default = config('app.locale', 'id');
        }

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
