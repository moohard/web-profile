<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GalleryImage;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GalleryImage> */
class GalleryImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gallery_id' => GalleryFactory::new(),
            'path' => 'galleries/'.$this->faker->uuid().'.jpg',
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Buat gallery image + translation untuk locale default (atau yang diberikan).
     */
    public function withTranslation(?string $locale = null): self
    {
        $locale ??= 'id';
        $languageId = Language::idFor($locale);

        return $this->has(
            GalleryImageTranslationFactory::new()->state([
                'language_id' => $languageId,
                'caption' => $this->faker->optional(0.6)->sentence(6),
            ]),
            'translations'
        );
    }
}
