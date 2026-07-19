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
}
