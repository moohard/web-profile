<?php

declare(strict_types=1);

namespace App\Services\Html;

use App\Enums\PageMode;
use Stevebauman\Purify\Facades\Purify;

class Sanitizer
{
    /**
     * Alias kompatibilitas untuk HTML mode Code Page.
     */
    public function clean(string $html): string
    {
        return $this->cleanCmsPage($html);
    }

    /**
     * Bersihkan HTML admin untuk mode Code Page.
     *
     * Buang script, event handler on*, dan URI berbahaya, tetapi pertahankan
     * class design system yang diizinkan profile CMS.
     */
    public function cleanCmsPage(string $html): string
    {
        return $this->cleanWithProfile($html, 'cms_page');
    }

    private function cleanWithProfile(string $html, string $profile): string
    {
        $cleaned = Purify::config($profile)->clean($html);

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

    /**
     * Bersihkan HTML sesuai mode Page — menyatukan logika pemilihan profil
     * yang sebelumnya terduplikasi verbatim di Admin\PageController (saat
     * simpan) & Public\PageController (saat tampil, defense-in-depth), supaya
     * kedua sisi tidak bisa silent-drift satu sama lain. Code (markup bebas)
     * pakai profil cms_page; Template (editor Tiptap) pakai profil rich-text.
     */
    public function cleanForPageMode(string $html, PageMode $mode): string
    {
        return $mode === PageMode::Code ? $this->clean($html) : $this->cleanRichText($html);
    }
}
