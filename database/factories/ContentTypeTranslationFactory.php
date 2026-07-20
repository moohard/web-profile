<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ContentTypeTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContentTypeTranslation> */
class ContentTypeTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'content_type_id' => null,
            'language_id' => null,
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
        ];
    }
}
