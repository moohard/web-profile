<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rating;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rating> */
class RatingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'comment' => $this->faker->optional(0.6)->sentence(8),
            'visitor_hash' => $this->faker->regexify('[a-f0-9]{64}'),
        ];
    }
}
