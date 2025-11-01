<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuthenticationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_onboarding_incomplete(): void
    {
        Event::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->onboarding_completed);
        $this->assertNull($user->email_verified_at);

        Event::assertDispatched(Registered::class);
    }

    public function test_user_model_implements_must_verify_email(): void
    {
        $user = new User();
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\MustVerifyEmail::class, $user);
    }

    public function test_user_has_onboarding_methods(): void
    {
        $user = User::factory()->create(['onboarding_completed' => false]);

        $this->assertFalse($user->hasCompletedOnboarding());

        $user->markOnboardingComplete();
        $user->refresh();

        $this->assertTrue($user->hasCompletedOnboarding());
    }

    public function test_dashboard_requires_verified_email(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }
}