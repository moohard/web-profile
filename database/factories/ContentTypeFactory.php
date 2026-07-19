<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ContentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContentType> */
class ContentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(1),
            'icon' => null,
            'writing_style_id' => null,
            'archive_template_key' => 'default',
            'single_template_key' => 'default',
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
