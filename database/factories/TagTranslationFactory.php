<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TagTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TagTranslation> */
class TagTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tag_id' => null,
            'language_id' => null,
            'name' => $this->faker->unique()->word(),
        ];
    }
}
