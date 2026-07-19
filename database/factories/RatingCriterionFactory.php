<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RatingCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RatingCriterion> */
class RatingCriterionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 20),
        ];
    }
}
