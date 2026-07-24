<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GalleryImageTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GalleryImageTranslation> */
class GalleryImageTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gallery_image_id' => null,
            'language_id' => null,
            'caption' => $this->faker->optional()->sentence(6),
        ];
    }
}
