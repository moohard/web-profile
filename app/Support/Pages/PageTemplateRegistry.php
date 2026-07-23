<?php

declare(strict_types=1);

namespace App\Support\Pages;

class PageTemplateRegistry
{
    /**
     * @return list<array{key: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['key' => 'default', 'label' => 'Default'],
            ['key' => 'full-width', 'label' => 'Full width'],
            ['key' => 'landing', 'label' => 'Landing'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::options(), 'key');
    }

    public static function resolve(string $key): string
    {
        return in_array($key, self::keys(), true) ? $key : 'default';
    }
}
