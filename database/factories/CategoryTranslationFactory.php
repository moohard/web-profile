<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CategoryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CategoryTranslation> */
class CategoryTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => null,
            'language_id' => null,
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
