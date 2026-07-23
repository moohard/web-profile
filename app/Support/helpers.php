<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\SettingTranslation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

if (! function_exists('excerpt')) {
    /**
     * Ubah HTML menjadi ringkasan teks polos yang Unicode-safe.
     */
    function excerpt(?string $html, int $limit = 160): string
    {
        if ($html === null || $limit <= 0) {
            return '';
        }

        $withBlockSpacing = preg_replace(
            '/<\s*\/?\s*(?:p|div|h[1-6]|li|blockquote|br)\b[^>]*>/iu',
            ' ',
            $html,
        ) ?? $html;
        // Decode entity SEBELUM strip_tags supaya payload yang bersembunyi di
        // entity (mis. &lt;script&gt;) terurai dulu lalu dilucuti sebagai tag —
        // mencegah tag re-injection pada output meta description/excerpt.
        $decoded = html_entity_decode(
            $withBlockSpacing,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );
        $plainText = strip_tags($decoded);
        $normalized = Str::squish($plainText);

        if ($normalized === '') {
            return '';
        }

        $length = grapheme_strlen($normalized);

        if ($length <= $limit) {
            return $normalized;
        }

        $truncated = grapheme_substr($normalized, 0, $limit);

        if ($truncated === false) {
            return '';
        }

        $nextGrapheme = grapheme_substr($normalized, $limit, 1) ?: '';

        if (
            preg_match('/\s/u', $truncated) === 1
            && preg_match('/\S$/u', $truncated) === 1
            && preg_match('/^\S/u', $nextGrapheme) === 1
        ) {
            $withoutPartialWord = preg_replace('/\s+\S*$/u', '', $truncated);

            if (is_string($withoutPartialWord) && $withoutPartialWord !== '') {
                $truncated = $withoutPartialWord;
            }
        }

        return rtrim($truncated).'…';
    }
}

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
