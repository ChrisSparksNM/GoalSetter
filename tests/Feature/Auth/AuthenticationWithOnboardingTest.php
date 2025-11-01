<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationWithOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_redirected_to_onboarding_after_login(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('onboarding.video'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_completed_onboarding_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_unverified_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'onboarding_completed' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_without_onboarding_cannot_access_dashboard_directly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('onboarding.video'));
    }

    public function test_user_without_onboarding_cannot_access_profile_directly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertRedirect(route('onboarding.video'));
    }

    public function test_user_with_completed_onboarding_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_user_with_completed_onboarding_can_access_profile(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    public function test_onboarding_completion_updates_user_status(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->post('/onboarding/complete');

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue($user->fresh()->hasCompletedOnboarding());
    }

    public function test_login_page_displays_email_verification_error_properly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertRedirect();
        
        // Follow the redirect to see the login page with errors
        $loginResponse = $this->get('/login');
        $loginResponse->assertOk();
    }

    public function test_login_with_invalid_credentials_shows_error(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_middleware_allows_access_to_onboarding_video_without_completion(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/onboarding/video');

        $response->assertOk();
    }

    public function test_user_can_access_onboarding_video_even_after_completion(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $response = $this->actingAs($user)->get('/onboarding/video');

        $response->assertOk();
    }
}