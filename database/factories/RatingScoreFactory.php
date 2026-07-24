<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RatingScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RatingScore> */
class RatingScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rating_id' => RatingFactory::new(),
            'criterion_id' => RatingCriterionFactory::new(),
            'score' => $this->faker->numberBetween(1, 5),
        ];
    }
}
