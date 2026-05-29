<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\OrgInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrgInquiry>
 */
class OrgInquiryFactory extends Factory
{
    protected $model = OrgInquiry::class;

    public function definition(): array
    {
        return [
            'lead_id'       => Lead::factory()->org(),
            'about'         => $this->faker->paragraph(),
            'role'          => $this->faker->jobTitle(),
            'organization'  => $this->faker->company(),
            'contact_kind'  => $this->faker->randomElement(['email', 'phone']),
            'contact_value' => $this->faker->safeEmail(),
        ];
    }
}
