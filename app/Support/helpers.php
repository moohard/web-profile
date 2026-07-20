<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\SettingTranslation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

if (! function_exists('setting_translated')) {
    /**
     * Ambil nilai teks setting yang diterjemahkan.
     * Fallback: locale aktif → bahasa default → null.
     *
     * @param  string  $key  contoh: 'site.tagline'
     */
    function setting_translated(string $key, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return Cache::rememberForever("setting_translated.{$key}.{$locale}", function () use ($key, $locale) {
            $value = SettingTranslation::where('key', $key)
                ->where('language_id', Language::idFor($locale))
                ->value('value');
            if ($value !== null) {
                return $value;
            }

            return SettingTranslation::where('key', $key)
                ->where('language_id', Language::defaultModel()->id)
                ->value('value');
        });
    }
}

if (! function_exists('setting_translated_flush')) {
    function setting_translated_flush(string $key): void
    {
        Language::all()->each(fn (Language $l) => Cache::forget("setting_translated.{$key}.{$l->code}")
        );
    }
}

if (! function_exists('excerpt')) {
    /**
     * Ringkas HTML jadi teks polos: strip tag, decode entity, rapikan whitespace,
     * lalu potong ke $limit karakter (multibyte-safe) + elipsis bila melebihi limit.
     * Dipakai untuk excerpt kartu arsip & fallback meta description/OG.
     */
    function excerpt(?string $html, int $limit = 160): string
    {
        if ($html === null) {
            return '';
        }

        $text = Str::squish(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit).'…';
    }
}
