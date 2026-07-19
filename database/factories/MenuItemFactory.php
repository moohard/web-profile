<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LinkType;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MenuItem> */
class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'parent_id' => null,
            'link_type' => LinkType::Url,
            'link_ref' => null,
            'url' => '/',
            'sort_order' => $this->faker->numberBetween(0, 20),
        ];
    }
}
