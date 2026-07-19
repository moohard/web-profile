<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\PageTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PageTranslation> */
class PageTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'page_id' => null,
            'language_id' => null,
            'slug' => $this->faker->unique()->slug(2),
            'title' => $this->faker->sentence(3),
            'content' => ['html' => '<p>'.$this->faker->paragraph(2).'</p>'],
            'hero_heading' => null,
            'hero_subheading' => null,
            'hero_cta_text' => null,
            'hero_cta_link' => null,
            'status' => PostStatus::Draft->value,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }
}
