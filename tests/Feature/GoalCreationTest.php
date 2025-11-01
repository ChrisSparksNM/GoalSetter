<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class GoalCreationTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUserWithCompletedOnboarding(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
    }

    public function test_authenticated_user_can_access_goal_creation_page(): void
    {
        $user = $this->createVerifiedUserWithCompletedOnboarding();

        $response = $this->actingAs($user)->get('/goals/create');

        $response->assertStatus(200);
        $response->assertViewIs('goals.create');
    }

    public function test_unauthenticated_user_cannot_access_goal_creation_page(): void
    {
        $response = $this->get('/goals/create');

        $response->assertRedirect('/login');
    }

    public function test_unverified_user_cannot_access_goal_creation_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'onboarding_completed' => true,
        ]);

        $response = $this->actingAs($user)->get('/goals/create');

        $response->assertRedirect('/verify-email');
    }

    public function test_user_without_completed_onboarding_cannot_access_goal_creation(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/goals/create');

        $response->assertRedirect('/onboarding/video');
    }

    public function test_user_can_create_goal_with_valid_data(): void
    {
        $user = $this->createVerifiedUserWithCompletedOnboarding();

        $goalData = [
            'title' => 'Learn Laravel',
            'description' => 'Complete Laravel course by end of month',
            'end_date' => Carbon::tomorrow()->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->post('/goals', $goalData);

        $response->assertRedirect('/goals');
        $response->assertSessionHas('success', 'Goal created successfully!');

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'title' => 'Learn Laravel',
            'description' => 'Complete Laravel course by end of month',
            'status' => Goal::STATUS_ACTIVE,
        ]);

        $goal = Goal::where('user_id', $user->id)->first();
        $this->assertEquals(Carbon::tomorrow()->format('Y-m-d'), $goal->end_date->format('Y-m-d'));
    }

    public function test_goal_creation_requires_title(): void
    {
        $user = $this->createVerifiedUserWithCompletedOnboarding();

        $goalData = [
            'title' => '',
            'description' => 'Some description',
            'end_date' => Carbon::tomorrow()->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->post('/goals', $goalData);

        $response->assertSessionHasErrors(['title']);
        $this->assertDatabaseCount('goals', 0);
    }

    public function test_goal_creation_requires_future_end_date(): void
    {
        $user = $this->createVerifiedUserWithCompletedOnboarding();

        $goalData = [
            'title' => 'Learn Laravel',
            'description' => 'Some description',
            'end_date' => Carbon::yesterday()->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->post('/goals', $goalData);

        $response->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseCount('goals', 0);
    }

    public function test_goal_creation_requires_valid_date_format(): void
    {
        $user = $this->createVerifiedUserWithCompletedOnboarding();

        $goalData = [
            'title' => 'Learn Laravel',
            'description' => 'Some description',
            'end_date' => 'invalid-date',
        ];

        $response = $this->actingAs($user)->post('/goals', $goalData);

        $response->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseCount('goals', 0);
    }

    public function test_goal_creation_with_description_is_optional(): void
    {
        $user = $this->createVerifiedUserWithCompletedOnboarding();

        $goalData = [
            'title' => 'Learn Laravel',
            'end_date' => Carbon::tomorrow()->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->post('/goals', $goalData);

        $response->assertRedirect('/goals');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'title' => 'Learn Laravel',
            'description' => null,
        ]);

        $goal = Goal::where('user_id', $user->id)->first();
        $this->assertEquals(Carbon::tomorrow()->format('Y-m-d'), $goal->end_date->format('Y-m-d'));
    }

    public function test_goal_title_cannot_exceed_255_characters(): void
    {
        $user = $this->createVerifiedUserWithCompletedOnboarding();

        $goalData = [
            'title' => str_repeat('a', 256),
            'description' => 'Some description',
            'end_date' => Carbon::tomorrow()->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->post('/goals', $goalData);

        $response->assertSessionHasErrors(['title']);
        $this->assertDatabaseCount('goals', 0);
    }

    public function test_goal_description_cannot_exceed_1000_characters(): void
    {
        $user = $this->createVerifiedUserWithCompletedOnboarding();

        $goalData = [
            'title' => 'Learn Laravel',
            'description' => str_repeat('a', 1001),
            'end_date' => Carbon::tomorrow()->format('Y-m-d'),
        ];

        $response = $this->actingAs($user)->post('/goals', $goalData);

        $response->assertSessionHasErrors(['description']);
        $this->assertDatabaseCount('goals', 0);
    }

    public function test_goal_creation_form_displays_validation_errors(): void
    {
        $user = $this->createVerifiedUserWithCompletedOnboarding();

        $goalData = [
            'title' => '',
            'end_date' => 'invalid-date',
        ];

        $response = $this->actingAs($user)->post('/goals', $goalData);

        $response->assertSessionHasErrors(['title', 'end_date']);
        
        // Test that the form redisplays with errors
        $response = $this->actingAs($user)->get('/goals/create');
        $response->assertStatus(200);
    }

    public function test_goal_creation_preserves_old_input_on_validation_error(): void
    {
        $user = $this->createVerifiedUserWithCompletedOnboarding();

        $goalData = [
            'title' => 'Valid Title',
            'description' => 'Valid description',
            'end_date' => 'invalid-date',
        ];

        $response = $this->actingAs($user)->post('/goals', $goalData);

        $response->assertSessionHasErrors(['end_date']);
        $response->assertSessionHasInput(['title', 'description']);
    }
}