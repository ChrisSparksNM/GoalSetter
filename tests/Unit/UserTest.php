<?php

namespace Tests\Unit;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_valid_data(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'onboarding_completed' => false,
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertFalse($user->onboarding_completed);
    }

    public function test_user_implements_must_verify_email(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(\Illuminate\Contracts\Auth\MustVerifyEmail::class, $user);
    }

    public function test_user_has_many_goals(): void
    {
        $user = User::factory()->create();
        $goals = Goal::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->goals);
        $this->assertInstanceOf(Goal::class, $user->goals->first());
        
        foreach ($user->goals as $goal) {
            $this->assertEquals($user->id, $goal->user_id);
        }
    }

    public function test_has_completed_onboarding_returns_correct_value(): void
    {
        $userWithIncompleteOnboarding = User::factory()->create([
            'onboarding_completed' => false,
        ]);
        
        $userWithCompleteOnboarding = User::factory()->create([
            'onboarding_completed' => true,
        ]);

        $this->assertFalse($userWithIncompleteOnboarding->hasCompletedOnboarding());
        $this->assertTrue($userWithCompleteOnboarding->hasCompletedOnboarding());
    }

    public function test_mark_onboarding_complete_updates_database(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => false,
        ]);

        $this->assertFalse($user->hasCompletedOnboarding());

        $user->markOnboardingComplete();

        $this->assertTrue($user->hasCompletedOnboarding());
        
        // Verify database was updated
        $user->refresh();
        $this->assertTrue($user->hasCompletedOnboarding());
        $this->assertTrue($user->onboarding_completed);
    }

    public function test_user_casts_are_properly_configured(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
        $this->assertIsBool($user->onboarding_completed);
    }

    public function test_user_fillable_attributes(): void
    {
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'onboarding_completed' => true,
        ];

        $user = new User();
        $user->fill($userData);

        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
        // Password gets hashed automatically, so we check if it's hashed
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(\Hash::check('password123', $user->password));
        $this->assertTrue($user->onboarding_completed);
    }

    public function test_user_hidden_attributes(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create([
            'password' => 'plaintext-password',
        ]);

        $this->assertNotEquals('plaintext-password', $user->getAuthPassword());
        $this->assertTrue(\Hash::check('plaintext-password', $user->getAuthPassword()));
    }
}