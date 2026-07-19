<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WritingStyle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WritingStyle> */
class WritingStyleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'prompt' => $this->faker->paragraph(),
        ];
    }
}
