<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Testimonial> */
class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'author_name' => $this->faker->name(),
            'author_title' => $this->faker->optional()->jobTitle(),
            'content' => $this->faker->paragraph(),
            'photo_media_id' => null,
            'status' => TestimonialStatus::Pending,
            'sort_order' => $this->faker->numberBetween(0, 20),
        ];
    }
}
