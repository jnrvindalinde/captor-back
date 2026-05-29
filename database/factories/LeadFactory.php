<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'uuid'             => (string) Str::uuid(),
            'kind'             => $this->faker->randomElement([
                Lead::KIND_CONTACT,
                Lead::KIND_ORG,
                Lead::KIND_APPLICATION,
            ]),
            'status'           => 'new',
            'assigned_user_id' => null,
            'name'             => $this->faker->name(),
            'email'            => $this->faker->safeEmail(),
            'phone'            => $this->faker->e164PhoneNumber(),
            'source'           => $this->faker->randomElement(['linkedin', 'instagram', 'referral', 'web']),
            'scheduled_at'     => null,
            'tags'             => [],
        ];
    }

    public function contact(): static
    {
        return $this->state(fn () => ['kind' => Lead::KIND_CONTACT]);
    }

    public function org(): static
    {
        return $this->state(fn () => ['kind' => Lead::KIND_ORG]);
    }

    public function application(): static
    {
        return $this->state(fn () => ['kind' => Lead::KIND_APPLICATION]);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => ['assigned_user_id' => $user->id]);
    }
}
