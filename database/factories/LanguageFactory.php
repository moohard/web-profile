<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Language> */
class LanguageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->languageCode(),
            'name' => $this->faker->word(),
            'is_default' => false,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
