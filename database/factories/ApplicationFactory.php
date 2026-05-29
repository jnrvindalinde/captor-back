<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'lead_id'       => Lead::factory()->application(),
            'status_self'   => $this->faker->randomElement(['student-final', 'graduate-recent', 'professional', 'senior', 'other']),
            'status_other'  => null,
            'location'      => $this->faker->city().', '.$this->faker->country(),
            'field'         => $this->faker->randomElement(['Computer Science', 'Mechanical Engineering', 'Banking', 'Medicine', 'Law']),
            'goal'          => $this->faker->randomElement(['study-abroad', 'local-job', 'international-placement', 'pivot', 'postgrad-gh', 'other']),
            'goal_other'    => null,
            'targets'       => $this->faker->randomElements(['United Kingdom', 'Germany', 'Netherlands', 'United States', 'Canada', 'France', 'Singapore'], 2),
            'timeline'      => $this->faker->randomElement(['0-3', '3-6', '6-12', '12+']),
            'budget'        => $this->faker->randomElement(['self', 'scholarship', 'employer', 'unsure']),
            'story'         => $this->faker->paragraph(),
            'newsletter'    => $this->faker->boolean(),
            'decision'      => Application::DECISION_PENDING,
            'decision_note' => null,
            'decided_at'    => null,
            'decided_by'    => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'decision'   => Application::DECISION_APPROVED,
            'decided_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn () => [
            'decision'   => Application::DECISION_DECLINED,
            'decided_at' => now(),
        ]);
    }
}
