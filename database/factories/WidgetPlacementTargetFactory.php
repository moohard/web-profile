<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WidgetPlacementTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WidgetPlacementTarget> */
class WidgetPlacementTargetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'placement_id' => WidgetPlacementFactory::new(),
            'target_type' => WidgetPlacementTarget::TYPE_PAGE,
            'target_ref' => (string) $this->faker->numberBetween(1, 999),
        ];
    }
}
