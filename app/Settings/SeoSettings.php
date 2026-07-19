<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SeoSettings extends Settings
{
    public ?string $default_meta_title;

    public ?string $default_meta_description;

    public ?string $og_default_image_path;

    public string $default_og_type = 'website';

    public static function group(): string
    {
        return 'seo';
    }
}
