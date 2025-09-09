<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipient>
 */
class RecipientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => \App\Models\Message::factory(),
            'phone_number' => $this->faker->phoneNumber(),
            'name' => $this->faker->optional()->name(),
            'status' => $this->faker->randomElement(['pending', 'sent', 'failed']),
            'external_message_id' => $this->faker->optional()->regexify('msg_[a-zA-Z0-9]{10}'),
            'sent_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'error_message' => $this->faker->optional()->sentence(),
        ];
    }
}
