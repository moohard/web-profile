<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\PostTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type_id' => null, // wajib di-set saat make
            'category_id' => null,
            'featured_image' => null,
        ];
    }

    /** Buat post + translation untuk locale tertentu. */
    public function withTranslation(string $locale, int $languageId, array $translationAttrs = []): self
    {
        return $this->has(
            PostTranslation::factory()->state(array_merge([
                'language_id' => $languageId,
                'slug' => $this->faker->unique()->slug(2),
                'title' => $this->faker->sentence(4),
                'body' => '<p>'.$this->faker->paragraph(3).'</p>',
                'status' => PostStatus::Published,
                'published_at' => now(),
            ], $translationAttrs)),
            'translations'
        );
    }
}
