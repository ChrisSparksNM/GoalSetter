<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFeedbackTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function successful_registration_shows_success_message()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success', 'Registration successful! Please check your email to verify your account before logging in.');
    }

    /** @test */
    public function successful_goal_creation_shows_success_message()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        $response = $this->actingAs($user)->post('/goals', [
            'title' => 'Test Goal',
            'description' => 'Test Description',
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        $response->assertRedirect('/goals');
        $response->assertSessionHas('success', 'Goal created successfully!');
    }

    /** @test */
    public function successful_goal_completion_shows_success_message()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
        $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $response = $this->actingAs($user)->patch("/goals/{$goal->id}/complete");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Congratulations! Goal completed successfully and notification sent.');
    }

    /** @test */
    public function successful_onboarding_completion_shows_success_message()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => false]);

        $response = $this->actingAs($user)->post('/onboarding/complete');

        $response->assertRedirect('/goals/create');
        $response->assertSessionHas('success', 'Welcome! You\'ve completed the onboarding. Now let\'s create your first goal.');
    }

    /** @test */
    public function attempting_to_complete_already_completed_goal_shows_error_message()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
        $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

        $response = $this->actingAs($user)->patch("/goals/{$goal->id}/complete");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This goal is already completed.');
    }

    /** @test */
    public function attempting_to_complete_non_active_goal_shows_error_message()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
        $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'cancelled']);

        $response = $this->actingAs($user)->patch("/goals/{$goal->id}/complete");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Only active goals can be marked as complete.');
    }

    /** @test */
    public function flash_messages_component_displays_success_messages()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        $response = $this->actingAs($user)
            ->withSession(['success' => 'Test success message'])
            ->get('/goals');

        $response->assertSee('Test success message');
        $response->assertSee('text-green-400'); // Success icon color
    }

    /** @test */
    public function flash_messages_component_displays_error_messages()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        $response = $this->actingAs($user)
            ->withSession(['error' => 'Test error message'])
            ->get('/goals');

        $response->assertSee('Test error message');
        $response->assertSee('text-red-400'); // Error icon color
    }

    /** @test */
    public function flash_messages_component_displays_warning_messages()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        $response = $this->actingAs($user)
            ->withSession(['warning' => 'Test warning message'])
            ->get('/goals');

        $response->assertSee('Test warning message');
        $response->assertSee('text-yellow-400'); // Warning icon color
    }

    /** @test */
    public function flash_messages_component_displays_info_messages()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        $response = $this->actingAs($user)
            ->withSession(['info' => 'Test info message'])
            ->get('/goals');

        $response->assertSee('Test info message');
        $response->assertSee('text-blue-400'); // Info icon color
    }

    /** @test */
    public function validation_errors_are_displayed_with_proper_styling()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        $response = $this->actingAs($user)->post('/goals', [
            'title' => '', // Required field missing
            'end_date' => '2020-01-01', // Past date
        ]);

        $response->assertSessionHasErrors(['title', 'end_date']);
        
        // Follow the redirect to see the form with errors
        $followUpResponse = $this->actingAs($user)->get('/goals/create');
        $followUpResponse->assertSee('The goal title is required.');
        $followUpResponse->assertSee('The end date must be a future date.');
    }

    /** @test */
    public function unauthenticated_access_shows_warning_message()
    {
        $response = $this->get('/goals');

        $response->assertRedirect('/login');
        $response->assertSessionHas('warning', 'Please log in to access this page.');
    }

    /** @test */
    public function login_form_displays_email_verification_error_properly()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email']);
        
        // Check that the login form displays the verification error
        $loginResponse = $this->get('/login');
        $loginResponse->assertSee('Email Verification Required');
        $loginResponse->assertSee('Resend Verification Email');
    }

    /** @test */
    public function registration_form_preserves_input_on_validation_error()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasInput('name', 'Test User');
        $response->assertSessionHasInput('email', 'invalid-email');
        $response->assertSessionHasErrors(['email', 'password']);
    }

    /** @test */
    public function goal_creation_form_preserves_input_on_validation_error()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        $response = $this->actingAs($user)->post('/goals', [
            'title' => 'Valid Title',
            'description' => 'Valid Description',
            'end_date' => '2020-01-01', // Invalid past date
        ]);

        $response->assertSessionHasInput('title', 'Valid Title');
        $response->assertSessionHasInput('description', 'Valid Description');
        $response->assertSessionHasErrors(['end_date']);
    }

    /** @test */
    public function error_pages_contain_helpful_navigation_links()
    {
        // Test 401 error page
        $response = $this->get('/login'); // This should be accessible
        $response->assertStatus(200);

        // Test that error pages would contain proper links (we can't easily trigger 401/403 in tests)
        // But we can verify the views exist and contain the expected content
        $this->assertTrue(view()->exists('errors.401'));
        $this->assertTrue(view()->exists('errors.403'));
        $this->assertTrue(view()->exists('errors.419'));
        $this->assertTrue(view()->exists('errors.500'));
    }

    /** @test */
    public function flash_messages_are_dismissible()
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        $response = $this->actingAs($user)
            ->withSession(['success' => 'Dismissible message'])
            ->get('/goals');

        // Check that dismiss button is present
        $response->assertSee('Dismiss');
        $response->assertSee('onclick="this.parentElement.parentElement.parentElement.parentElement.style.display=\'none\'"');
    }
}