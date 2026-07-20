<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ContentType;
use App\Models\ContentTypeTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContentType> */
class ContentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(1),
            'icon' => null,
            'writing_style_id' => null,
            'archive_template_key' => 'default',
            'single_template_key' => 'default',
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Buat jenis konten + translation untuk locale tertentu.
     *
     * @param  array<string, mixed>  $translationAttrs
     */
    public function withTranslation(string $locale, int $languageId, array $translationAttrs = []): self
    {
        return $this->has(
            ContentTypeTranslation::factory()->state(array_merge([
                'language_id' => $languageId,
                'name' => $this->faker->unique()->words(2, true),
            ], $translationAttrs)),
            'translations'
        );
    }
}
