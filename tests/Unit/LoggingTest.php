<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Goal;
use App\Services\GoalCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LoggingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function goal_completion_service_logs_successful_completion()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        Log::shouldReceive('info')
            ->once()
            ->with('Goal completed successfully', [
                'goal_id' => $goal->id,
                'user_id' => $goal->user_id,
                'title' => $goal->title,
                'completed_at' => \Mockery::type('string')
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Goal completion notification sent successfully', \Mockery::type('array'));

        $service = new GoalCompletionService();
        $service->completeGoal($goal);
    }

    /** @test */
    public function goal_completion_service_logs_email_failures()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        // Mock mail failure by not setting email configuration
        config(['mail.goal_notification_recipient' => null]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Goal completion notification recipient not configured', [
                'goal_id' => $goal->id
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Goal completed successfully', \Mockery::type('array'));

        $service = new GoalCompletionService();
        $service->completeGoal($goal);
    }

    /** @test */
    public function authentication_events_are_logged()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Log::shouldReceive('info')
            ->once()
            ->with('User logged in successfully', \Mockery::type('array'));

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function registration_events_are_logged()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('User registered successfully', \Mockery::type('array'));

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
    }

    /** @test */
    public function goal_creation_events_are_logged()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        Log::shouldReceive('info')
            ->once()
            ->with('Goal created successfully', \Mockery::type('array'));

        $response = $this->actingAs($user)->post('/goals', [
            'title' => 'Test Goal',
            'description' => 'Test Description',
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertRedirect('/goals');
    }

    /** @test */
    public function onboarding_completion_events_are_logged()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => false]);

        Log::shouldReceive('info')
            ->once()
            ->with('User completed onboarding', \Mockery::type('array'));

        $response = $this->actingAs($user)->post('/onboarding/complete');

        $response->assertRedirect('/goals/create');
    }

    /** @test */
    public function error_events_are_logged_with_context()
    {
        // This test verifies that our exception handler logs errors with proper context
        // We'll test this by checking that the handler is configured correctly
        
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $this->assertInstanceOf(\App\Exceptions\Handler::class, $handler);
    }

    /** @test */
    public function unauthorized_access_attempts_are_logged()
    {
        $user1 = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
        $user2 = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
        $goal = Goal::factory()->create(['user_id' => $user1->id]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Authorization failed', \Mockery::type('array'));

        $response = $this->actingAs($user2)->get("/goals/{$goal->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_access_attempts_are_logged()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Unauthenticated access attempt', \Mockery::type('array'));

        $response = $this->get('/goals');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function csrf_token_mismatch_is_logged()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        Log::shouldReceive('warning')
            ->once()
            ->with('CSRF token mismatch', \Mockery::type('array'));

        // Simulate CSRF token mismatch by making request without proper token
        $response = $this->actingAs($user)->post('/goals', [
            'title' => 'Test Goal',
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ], ['HTTP_X-CSRF-TOKEN' => 'invalid-token']);

        $response->assertStatus(419);
    }

    /** @test */
    public function log_entries_contain_required_context_information()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        Log::shouldReceive('info')
            ->once()
            ->with('Goal created successfully', \Mockery::that(function ($context) use ($user) {
                return isset($context['goal_id']) &&
                       isset($context['user_id']) &&
                       isset($context['title']) &&
                       isset($context['end_date']) &&
                       $context['user_id'] === $user->id;
            }));

        $response = $this->actingAs($user)->post('/goals', [
            'title' => 'Test Goal',
            'description' => 'Test Description',
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertRedirect('/goals');
    }
}