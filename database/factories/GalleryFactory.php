<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Gallery> */
class GalleryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'is_active' => true,
        ];
    }

    /**
     * Buat gallery + translation untuk locale default (atau yang diberikan).
     */
    public function withTranslation(?string $locale = null): self
    {
        $locale ??= 'id';
        $languageId = Language::idFor($locale);

        return $this->has(
            GalleryTranslationFactory::new()->state([
                'language_id' => $languageId,
                'title' => $this->faker->sentence(3),
                'description' => $this->faker->optional(0.8)->paragraph(2),
            ]),
            'translations'
        );
    }
}
