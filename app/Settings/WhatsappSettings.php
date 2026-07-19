<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WhatsappSettings extends Settings
{
    public string $number = '';

    public bool $enabled = false;

    public string $default_message = '';

    public static function group(): string
    {
        return 'whatsapp';
    }
}
