<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GalleryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GalleryTranslation> */
class GalleryTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gallery_id' => null,
            'language_id' => null,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(2),
        ];
    }
}
