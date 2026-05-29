<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'uuid'                  => (string) Str::uuid(),
            'name'                  => fake()->name(),
            'email'                 => fake()->safeEmail(),
            'phone'                 => fake()->optional()->phoneNumber(),
            'program'               => fake()->randomElement(Client::PROGRAMS),
            'consultant_id'         => User::query()->where('role', 'admin')->value('id'),
            'status'                => fake()->randomElement(Client::STATUSES),
            'start_date'            => now()->subDays(fake()->numberBetween(0, 90)),
            'next_milestone_label'  => fake()->optional()->sentence(3),
            'next_milestone_due_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'satisfaction'          => fake()->optional()->numberBetween(3, 5),
            'source_lead_id'        => null,
        ];
    }
}
