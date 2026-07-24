<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SiteSettings extends Settings
{
    public ?string $address;

    public string $phone = '';

    public ?string $email;

    /** @var array<string, string> */
    public array $social_links = [];

    public ?string $maps_embed;

    public ?string $contact_notification_email;

    public ?string $logo_path;

    public ?string $favicon_path;

    public string $site_name;

    public static function group(): string
    {
        return 'site';
    }
}
