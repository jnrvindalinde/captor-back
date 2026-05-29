<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'lead_id'         => Lead::factory(),
            'scheduled_by'    => User::factory(),
            'scheduled_at'    => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'google_event_id' => 'gcal_'.$this->faker->lexify('????????????????'),
            'google_meet_link'=> 'https://meet.google.com/'.$this->faker->lexify('???-????-???'),
            'status'          => 'scheduled',
            'notes'           => null,
        ];
    }
}
