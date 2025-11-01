<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_onboarding_video(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/onboarding/video');

        $response->assertStatus(200);
        $response->assertViewIs('onboarding.video');
        $response->assertViewHas('videoUrl');
        $response->assertViewHas('user', $user);
    }

    public function test_unauthenticated_user_cannot_access_onboarding_video(): void
    {
        $response = $this->get('/onboarding/video');

        $response->assertRedirect('/login');
    }

    public function test_unverified_user_cannot_access_onboarding_video(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/onboarding/video');

        $response->assertRedirect('/verify-email');
    }

    public function test_user_who_completed_onboarding_is_redirected_to_goals(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $response = $this->actingAs($user)->get('/onboarding/video');

        $response->assertRedirect('/goals');
    }

    public function test_user_can_complete_onboarding(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->post('/onboarding/complete');

        $response->assertRedirect('/goals/create');
        $response->assertSessionHas('success');
        
        $user->refresh();
        $this->assertTrue($user->hasCompletedOnboarding());
    }

    public function test_unauthenticated_user_cannot_complete_onboarding(): void
    {
        $response = $this->post('/onboarding/complete');

        $response->assertRedirect('/login');
    }

    public function test_unverified_user_cannot_complete_onboarding(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->post('/onboarding/complete');

        $response->assertRedirect('/verify-email');
    }

    public function test_onboarding_video_page_contains_required_elements(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/onboarding/video');

        $response->assertStatus(200);
        $response->assertSee('Welcome, ' . $user->name);
        $response->assertSee('Setting Smart Goals');
        $response->assertSee('Continue to Goal Creation');
        $response->assertSee('Skip Video');
        $response->assertSee('onboarding-video');
    }

    public function test_video_url_is_accessible(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/onboarding/video');
        
        $videoUrl = $response->viewData('videoUrl');
        $this->assertStringContainsString('setting-smart-goals.mp4', $videoUrl);
    }

    public function test_user_model_onboarding_methods(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => false,
        ]);

        // Test initial state
        $this->assertFalse($user->hasCompletedOnboarding());

        // Test marking as complete
        $user->markOnboardingComplete();
        $this->assertTrue($user->hasCompletedOnboarding());

        // Verify database was updated
        $user->refresh();
        $this->assertTrue($user->hasCompletedOnboarding());
    }

    public function test_onboarding_completion_redirects_to_goal_creation(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->post('/onboarding/complete');

        $response->assertRedirect('/goals/create');
        $response->assertSessionHas('success', 'Welcome! You\'ve completed the onboarding. Now let\'s create your first goal.');
    }

    public function test_onboarding_controller_middleware_is_applied(): void
    {
        // Test that auth middleware is applied
        $response = $this->get('/onboarding/video');
        $response->assertRedirect('/login');

        // Test that verified middleware is applied
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);
        
        $response = $this->actingAs($unverifiedUser)->get('/onboarding/video');
        $response->assertRedirect('/verify-email');
    }
}