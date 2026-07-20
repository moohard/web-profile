<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use App\Models\TagTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tag> */
class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(1),
        ];
    }

    /**
     * Buat tag + translation untuk locale tertentu.
     *
     * @param  array<string, mixed>  $translationAttrs
     */
    public function withTranslation(string $locale, int $languageId, array $translationAttrs = []): self
    {
        return $this->has(
            TagTranslation::factory()->state(array_merge([
                'language_id' => $languageId,
                'name' => $this->faker->unique()->word(),
            ], $translationAttrs)),
            'translations'
        );
    }
}
