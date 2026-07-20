<?php

declare(strict_types=1);

namespace App\Services\Html;

use Stevebauman\Purify\Facades\Purify;

class Sanitizer
{
    /**
     * Bersihkan HTML admin untuk mode code page.
     * Buang script, event handler on*, javascript/data URLs; pertahankan class design system.
     */
    public function clean(string $html): string
    {
        $cleaned = Purify::config('cms_page')->clean($html);

        return is_string($cleaned) ? $cleaned : '';
    }

    /**
     * Bersihkan HTML rich-text dari editor Tiptap (body Post & konten mode
     * Template Page). Profil `default`: izinkan heading/format dasar/list/
     * link/quote/gambar; buang script, event handler on*, javascript/data URL,
     * dan tag di luar allowlist rich-text (mis. div/table — beda dari cms_page).
     */
    public function cleanRichText(string $html): string
    {
        $cleaned = Purify::config('default')->clean($html);

        return is_string($cleaned) ? $cleaned : '';
    }
}
