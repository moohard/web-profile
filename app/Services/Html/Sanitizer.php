<?php

declare(strict_types=1);

namespace App\Services\Html;

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
     * Bersihkan HTML dari editor rich text dengan allowlist terbatas.
     */
    public function cleanRichText(string $html): string
    {
        return $this->cleanWithProfile($html, 'rich_text');
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
}
