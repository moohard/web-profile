<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(1),
            'parent_id' => null,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Buat kategori + translation untuk locale tertentu.
     *
     * @param  array<string, mixed>  $translationAttrs
     */
    public function withTranslation(string $locale, int $languageId, array $translationAttrs = []): self
    {
        return $this->has(
            CategoryTranslation::factory()->state(array_merge([
                'language_id' => $languageId,
                'name' => $this->faker->unique()->words(2, true),
            ], $translationAttrs)),
            'translations'
        );
    }
}
