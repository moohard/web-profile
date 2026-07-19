<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PageMode;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Page> */
class PageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mode' => PageMode::Template,
            'template_key' => 'default',
            'hero_enabled' => false,
            'hero_image' => null,
            'sidebar_enabled' => false,
        ];
    }
}
