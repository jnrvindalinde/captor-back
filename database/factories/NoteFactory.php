<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'lead_id'   => Lead::factory(),
            'author_id' => User::factory(),
            'kind'      => Note::KIND_MANUAL,
            'body'      => $this->faker->paragraph(),
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => ['kind' => Note::KIND_SYSTEM]);
    }
}
