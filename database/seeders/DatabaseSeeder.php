<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Goal;
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
        // Create a test user with verified email and completed onboarding
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        // Create sample goals for the test user
        Goal::factory()->count(3)->active()->create([
            'user_id' => $testUser->id,
        ]);

        Goal::factory()->count(2)->completed()->create([
            'user_id' => $testUser->id,
        ]);

        Goal::factory()->count(1)->overdue()->create([
            'user_id' => $testUser->id,
        ]);

        // Create additional users with goals for development testing
        $additionalUsers = User::factory()->count(5)->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        foreach ($additionalUsers as $user) {
            // Each user gets 2-5 random goals
            Goal::factory()->count(rand(2, 5))->create([
                'user_id' => $user->id,
            ]);
        }

        // Create a user who hasn't completed onboarding for testing
        User::factory()->create([
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        // Create an unverified user for testing email verification
        User::factory()->create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
            'onboarding_completed' => false,
        ]);
    }
}
