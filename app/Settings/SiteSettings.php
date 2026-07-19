<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SiteSettings extends Settings
{
    public ?string $logo_path;

    public ?string $favicon_path;

    public string $site_name;

    public static function group(): string
    {
        return 'site';
    }
}
