<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@career360consult.com'],
            [
                'name'         => 'Career360 Admin',
                'password'     => bcrypt('change-me-now'),
                'role'         => 'super_admin',
                'calendly_url' => env('CALENDLY_DEFAULT_URL'),
            ],
        );

        // Local dev admin — use these creds when running locally:
        //   email:    admin@local.com
        //   password: password
        User::updateOrCreate(
            ['email' => 'admin@local.com'],
            [
                'name'         => 'Local Admin',
                'password'     => bcrypt('password'),
                'role'         => 'super_admin',
                'calendly_url' => env('CALENDLY_DEFAULT_URL'),
            ],
        );
    }
}
