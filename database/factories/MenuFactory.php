<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MenuLocation;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Menu> */
class MenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'location' => MenuLocation::Header,
        ];
    }
}
