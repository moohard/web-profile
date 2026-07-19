<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Widget;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Widget> */
class WidgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'HtmlWidget',
            'config' => ['html' => '<p>'.$this->faker->sentence().'</p>'],
            'is_active' => true,
        ];
    }
}
