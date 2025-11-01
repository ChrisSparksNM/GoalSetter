<?php

namespace Tests;

use App\Models\Goal;
use App\Models\GoalNotification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Seed the test database with comprehensive test data.
     */
    public function run(): void
    {
        // Create test users with different states
        $this->createTestUsers();
        
        // Create test goals with various statuses
        $this->createTestGoals();
        
        // Create test notifications
        $this->createTestNotifications();
    }

    /**
     * Create test users with different onboarding and verification states.
     */
    private function createTestUsers(): void
    {
        // User with completed onboarding and verified email
        User::factory()->create([
            'name' => 'Complete User',
            'email' => 'complete@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        // User with verified email but incomplete onboarding
        User::factory()->create([
            'name' => 'Onboarding User',
            'email' => 'onboarding@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        // User with unverified email
        User::factory()->create([
            'name' => 'Unverified User',
            'email' => 'unverified@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => null,
            'onboarding_completed' => false,
        ]);

        // User with multiple goals for testing pagination and filtering
        $userWithManyGoals = User::factory()->create([
            'name' => 'Prolific User',
            'email' => 'prolific@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        // Create multiple goals for this user
        Goal::factory()->count(10)->create([
            'user_id' => $userWithManyGoals->id,
        ]);
    }

    /**
     * Create test goals with various statuses and dates.
     */
    private function createTestGoals(): void
    {
        $completeUser = User::where('email', 'complete@test.com')->first();

        // Active goal with future end date
        Goal::factory()->create([
            'user_id' => $completeUser->id,
            'title' => 'Future Active Goal',
            'description' => 'A goal with a future end date',
            'end_date' => now()->addMonth(),
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Overdue active goal
        Goal::factory()->create([
            'user_id' => $completeUser->id,
            'title' => 'Overdue Active Goal',
            'description' => 'A goal that is past its end date',
            'end_date' => now()->subWeek(),
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Completed goal
        Goal::factory()->create([
            'user_id' => $completeUser->id,
            'title' => 'Completed Goal',
            'description' => 'A goal that has been completed',
            'end_date' => now()->addWeek(),
            'status' => Goal::STATUS_COMPLETED,
            'completed_at' => now()->subDay(),
        ]);

        // Goal with long title and description for testing UI limits
        Goal::factory()->create([
            'user_id' => $completeUser->id,
            'title' => str_repeat('Long Goal Title ', 10),
            'description' => str_repeat('This is a very long description that tests how the UI handles lengthy content. ', 20),
            'end_date' => now()->addMonth(),
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Goal with special characters
        Goal::factory()->create([
            'user_id' => $completeUser->id,
            'title' => 'Spéciål Chåråctërs & Émojis 🎯',
            'description' => 'Testing special characters: àáâãäåæçèéêë & emojis 🚀🎉',
            'end_date' => now()->addWeeks(2),
            'status' => Goal::STATUS_ACTIVE,
        ]);
    }

    /**
     * Create test notifications with different statuses.
     */
    private function createTestNotifications(): void
    {
        $completeUser = User::where('email', 'complete@test.com')->first();
        $completedGoal = Goal::where('user_id', $completeUser->id)
            ->where('status', Goal::STATUS_COMPLETED)
            ->first();

        if ($completedGoal) {
            // Successful notification
            GoalNotification::factory()->create([
                'goal_id' => $completedGoal->id,
                'recipient_email' => 'admin@test.com',
                'status' => GoalNotification::STATUS_SENT,
                'sent_at' => now()->subHour(),
            ]);

            // Failed notification
            GoalNotification::factory()->create([
                'goal_id' => $completedGoal->id,
                'recipient_email' => 'invalid@test.com',
                'status' => GoalNotification::STATUS_FAILED,
                'sent_at' => null,
            ]);

            // Pending notification
            GoalNotification::factory()->create([
                'goal_id' => $completedGoal->id,
                'recipient_email' => 'pending@test.com',
                'status' => GoalNotification::STATUS_PENDING,
                'sent_at' => null,
            ]);
        }
    }

    /**
     * Create test data for performance testing.
     */
    public function createPerformanceTestData(): void
    {
        // Create a user with many goals for performance testing
        $performanceUser = User::factory()->create([
            'name' => 'Performance Test User',
            'email' => 'performance@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        // Create 100 goals with various statuses
        Goal::factory()->count(50)->create([
            'user_id' => $performanceUser->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        Goal::factory()->count(30)->create([
            'user_id' => $performanceUser->id,
            'status' => Goal::STATUS_COMPLETED,
            'completed_at' => now()->subDays(rand(1, 30)),
        ]);

        Goal::factory()->count(20)->create([
            'user_id' => $performanceUser->id,
            'status' => Goal::STATUS_CANCELLED,
        ]);
    }

    /**
     * Create test data for edge cases.
     */
    public function createEdgeCaseTestData(): void
    {
        $edgeCaseUser = User::factory()->create([
            'name' => 'Edge Case User',
            'email' => 'edge@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        // Goal with minimum valid end date (tomorrow)
        Goal::factory()->create([
            'user_id' => $edgeCaseUser->id,
            'title' => 'Minimum Date Goal',
            'end_date' => now()->addDay(),
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Goal with maximum title length
        Goal::factory()->create([
            'user_id' => $edgeCaseUser->id,
            'title' => str_repeat('A', 255),
            'description' => null, // Test null description
            'end_date' => now()->addMonth(),
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Goal with maximum description length
        Goal::factory()->create([
            'user_id' => $edgeCaseUser->id,
            'title' => 'Max Description Goal',
            'description' => str_repeat('B', 1000),
            'end_date' => now()->addMonth(),
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Goal completed exactly at midnight
        Goal::factory()->create([
            'user_id' => $edgeCaseUser->id,
            'title' => 'Midnight Completion Goal',
            'end_date' => now()->addWeek(),
            'status' => Goal::STATUS_COMPLETED,
            'completed_at' => now()->startOfDay(),
        ]);
    }
}