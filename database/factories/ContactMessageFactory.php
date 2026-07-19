<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactStatus;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactMessage> */
class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->optional()->numerify('08##########'),
            'subject' => $this->faker->optional()->sentence(3),
            'message' => $this->faker->paragraph(),
            'status' => ContactStatus::New,
        ];
    }
}
