<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PlacementScope;
use App\Enums\WidgetPosition;
use App\Models\Widget;
use App\Models\WidgetPlacement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WidgetPlacement> */
class WidgetPlacementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'widget_id' => Widget::factory(),
            'position' => WidgetPosition::Sidebar,
            'scope' => PlacementScope::All,
            'sort_order' => $this->faker->numberBetween(0, 20),
        ];
    }
}
