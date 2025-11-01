<?php

namespace Tests\Feature;

use App\Mail\GoalCompletionMail;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CompleteUserJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure email settings for testing
        Config::set('mail.goal_notification_recipient', 'admin@example.com');
        
        // Fake events and mail
        Event::fake();
        Mail::fake();
    }

    public function test_complete_user_journey_from_registration_to_goal_completion(): void
    {
        // Step 1: User Registration
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Registration should redirect
        $response->assertRedirect();
        
        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->hasCompletedOnboarding());
        $this->assertNull($user->email_verified_at);
        
        Event::assertDispatched(Registered::class);

        // Step 2: Login after registration
        $loginResponse = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);
        
        // Should redirect to email verification
        $loginResponse->assertRedirect();

        // Step 3: Email Verification (simulate)
        $user->markEmailAsVerified();
        $this->assertNotNull($user->email_verified_at);

        // Step 4: First Login - Try to access dashboard, should redirect to onboarding
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('onboarding.video'));

        // Step 5: View Onboarding Video
        $response = $this->actingAs($user)->get('/onboarding/video');
        $response->assertStatus(200);
        $response->assertViewIs('onboarding.video');
        $response->assertSee('Welcome, John Doe');
        $response->assertSee('Setting Smart Goals');

        // Step 6: Complete Onboarding
        $response = $this->actingAs($user)->post('/onboarding/complete');
        $response->assertRedirect('/goals/create');
        $response->assertSessionHas('success', 'Welcome! You\'ve completed the onboarding. Now let\'s create your first goal.');
        
        $user->refresh();
        $this->assertTrue($user->hasCompletedOnboarding());

        // Step 7: Create First Goal
        $goalData = [
            'title' => 'Learn Laravel Framework',
            'description' => 'Complete Laravel course and build a project',
            'end_date' => now()->addMonth()->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->post('/goals', $goalData);
        $response->assertRedirect('/goals');
        $response->assertSessionHas('success', 'Goal created successfully!');

        $goal = Goal::where('user_id', $user->id)->first();
        $this->assertNotNull($goal);
        $this->assertEquals('Learn Laravel Framework', $goal->title);
        $this->assertEquals(Goal::STATUS_ACTIVE, $goal->status);

        // Step 8: View Goals Dashboard
        $response = $this->actingAs($user)->get('/goals');
        $response->assertStatus(200);
        $response->assertViewIs('goals.index');
        $response->assertSee('Learn Laravel Framework');
        $response->assertSee('Mark Complete');

        // Step 9: View Individual Goal
        $response = $this->actingAs($user)->get(route('goals.show', $goal));
        $response->assertStatus(200);
        $response->assertViewIs('goals.show');
        $response->assertSee($goal->title);
        $response->assertSee($goal->description);

        // Step 10: Complete the Goal
        $response = $this->actingAs($user)->patch(route('goals.complete', $goal));
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Congratulations! Goal completed successfully and notification sent.');

        $goal->refresh();
        $this->assertEquals(Goal::STATUS_COMPLETED, $goal->status);
        $this->assertNotNull($goal->completed_at);

        // Step 11: Verify Email Notification was Sent
        Mail::assertSent(GoalCompletionMail::class, function ($mail) use ($goal) {
            return $mail->goal->id === $goal->id &&
                   $mail->hasTo('admin@example.com');
        });

        // Step 12: View Completed Goals
        $response = $this->actingAs($user)->get('/goals?status=completed');
        $response->assertStatus(200);
        $response->assertSee('Learn Laravel Framework');
        $response->assertSee('Completed');

        // Step 13: Create Additional Goal
        $secondGoalData = [
            'title' => 'Master Vue.js',
            'description' => 'Build a SPA with Vue.js',
            'end_date' => now()->addWeeks(2)->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->post('/goals', $secondGoalData);
        $response->assertRedirect('/goals');

        // Verify user now has 2 goals
        $this->assertCount(2, $user->goals);
        
        // Verify filtering works
        $response = $this->actingAs($user)->get('/goals?status=active');
        $response->assertStatus(200);
        $response->assertSee('Master Vue.js');
        $response->assertDontSee('Learn Laravel Framework'); // Completed goal shouldn't show in active filter
    }

    public function test_user_journey_with_email_verification_required(): void
    {
        // Register user
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        
        // Try to access dashboard without email verification
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('verification.notice'));

        // Try to access onboarding without email verification
        $response = $this->actingAs($user)->get('/onboarding/video');
        $response->assertRedirect('/verify-email');

        // Try to access goals without email verification
        $response = $this->actingAs($user)->get('/goals');
        $response->assertRedirect('/verify-email');

        // Verify email
        $user->markEmailAsVerified();

        // Now user can access dashboard and is redirected to onboarding
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect(route('onboarding.video'));
    }

    public function test_user_journey_skipping_onboarding_video(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        // Access onboarding video
        $response = $this->actingAs($user)->get('/onboarding/video');
        $response->assertStatus(200);

        // Skip video and complete onboarding directly
        $response = $this->actingAs($user)->post('/onboarding/complete');
        $response->assertRedirect('/goals/create');
        
        $user->refresh();
        $this->assertTrue($user->hasCompletedOnboarding());

        // Subsequent dashboard access should go to goals
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertRedirect('/goals');
    }

    public function test_user_journey_with_multiple_goal_statuses(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        // Create multiple goals
        $activeGoal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Active Goal',
            'status' => Goal::STATUS_ACTIVE,
        ]);

        $completedGoal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Completed Goal',
            'status' => Goal::STATUS_COMPLETED,
            'completed_at' => now()->subDay(),
        ]);

        // Test dashboard shows all goals
        $response = $this->actingAs($user)->get('/goals');
        $response->assertStatus(200);
        $response->assertSee('Active Goal');
        $response->assertSee('Completed Goal');

        // Test active filter
        $response = $this->actingAs($user)->get('/goals?status=active');
        $response->assertSee('Active Goal');
        $response->assertDontSee('Completed Goal');

        // Test completed filter
        $response = $this->actingAs($user)->get('/goals?status=completed');
        $response->assertSee('Completed Goal');
        $response->assertDontSee('Active Goal');

        // Complete the active goal
        $response = $this->actingAs($user)->patch(route('goals.complete', $activeGoal));
        $response->assertRedirect();

        // Verify both goals are now completed
        $response = $this->actingAs($user)->get('/goals?status=completed');
        $response->assertSee('Active Goal');
        $response->assertSee('Completed Goal');
    }

    public function test_user_journey_error_handling(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        // Test goal creation with invalid data
        $response = $this->actingAs($user)->post('/goals', [
            'title' => '', // Invalid: empty title
            'end_date' => 'invalid-date', // Invalid: bad date format
        ]);

        $response->assertSessionHasErrors(['title', 'end_date']);
        $this->assertDatabaseCount('goals', 0);

        // Test goal creation with past date
        $response = $this->actingAs($user)->post('/goals', [
            'title' => 'Valid Title',
            'end_date' => now()->subDay()->format('Y-m-d'), // Invalid: past date
        ]);

        $response->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseCount('goals', 0);

        // Test completing non-existent goal
        $response = $this->actingAs($user)->patch('/goals/999/complete');
        $response->assertNotFound();

        // Test accessing other user's goal
        $otherUser = User::factory()->create();
        $otherGoal = Goal::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('goals.show', $otherGoal));
        $response->assertForbidden();

        $response = $this->actingAs($user)->patch(route('goals.complete', $otherGoal));
        $response->assertForbidden();
    }
}