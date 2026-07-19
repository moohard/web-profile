<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\PostTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PostTranslation> */
class PostTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => null,
            'language_id' => null,
            'slug' => $this->faker->unique()->slug(2),
            'title' => $this->faker->sentence(4),
            'body' => '<p>'.$this->faker->paragraph(3).'</p>',
            'status' => PostStatus::Draft,
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }
}
