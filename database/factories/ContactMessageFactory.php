<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory()->contact(),
            'topic'   => $this->faker->randomElement(['applications', 'advising', 'partnerships', 'press', 'other']),
            'message' => $this->faker->paragraph(),
        ];
    }
}
