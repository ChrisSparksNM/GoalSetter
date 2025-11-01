<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GoalCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Log to prevent actual logging during tests
        Log::spy();
        
        // Fake Mail to prevent actual email sending during tests
        Mail::fake();
        
        // Clear email configuration to test warning scenario
        Config::set('mail.goal_notification_recipient', null);
    }

    public function test_authenticated_user_can_complete_their_own_goal(): void
    {
        // Arrange
        $user = User::factory()->create(['onboarding_completed' => true]);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->patch(route('goals.complete', $goal));

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Congratulations! Goal completed successfully and notification sent.');
        
        $goal->refresh();
        $this->assertEquals(Goal::STATUS_COMPLETED, $goal->status);
        $this->assertNotNull($goal->completed_at);
    }

    public function test_user_cannot_complete_another_users_goal(): void
    {
        // Arrange
        $user = User::factory()->create(['onboarding_completed' => true]);
        $otherUser = User::factory()->create(['onboarding_completed' => true]);
        $goal = Goal::factory()->create([
            'user_id' => $otherUser->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->patch(route('goals.complete', $goal));

        // Assert
        $response->assertForbidden();
        
        $goal->refresh();
        $this->assertEquals(Goal::STATUS_ACTIVE, $goal->status);
        $this->assertNull($goal->completed_at);
    }

    public function test_user_cannot_complete_already_completed_goal(): void
    {
        // Arrange
        $user = User::factory()->create(['onboarding_completed' => true]);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_COMPLETED,
            'completed_at' => now()->subDay(),
        ]);

        // Act
        $response = $this->actingAs($user)
            ->patch(route('goals.complete', $goal));

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('error', 'This goal is already completed.');
        
        $goal->refresh();
        $this->assertEquals(Goal::STATUS_COMPLETED, $goal->status);
    }

    public function test_user_cannot_complete_cancelled_goal(): void
    {
        // Arrange
        $user = User::factory()->create(['onboarding_completed' => true]);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_CANCELLED,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->patch(route('goals.complete', $goal));

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Only active goals can be marked as complete.');
        
        $goal->refresh();
        $this->assertEquals(Goal::STATUS_CANCELLED, $goal->status);
        $this->assertNull($goal->completed_at);
    }

    public function test_unauthenticated_user_cannot_complete_goal(): void
    {
        // Arrange
        $user = User::factory()->create(['onboarding_completed' => true]);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Act
        $response = $this->patch(route('goals.complete', $goal));

        // Assert
        $response->assertRedirect(route('login'));
        
        $goal->refresh();
        $this->assertEquals(Goal::STATUS_ACTIVE, $goal->status);
        $this->assertNull($goal->completed_at);
    }

    public function test_user_without_completed_onboarding_cannot_complete_goal(): void
    {
        // Arrange
        $user = User::factory()->create(['onboarding_completed' => false]);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->patch(route('goals.complete', $goal));

        // Assert
        $response->assertRedirect(route('onboarding.video'));
        
        $goal->refresh();
        $this->assertEquals(Goal::STATUS_ACTIVE, $goal->status);
        $this->assertNull($goal->completed_at);
    }

    public function test_goal_completion_logs_appropriate_information(): void
    {
        // Arrange
        $user = User::factory()->create(['onboarding_completed' => true]);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
            'title' => 'Test Goal',
        ]);

        // Act
        $this->actingAs($user)
            ->patch(route('goals.complete', $goal));

        // Assert
        Log::shouldHaveReceived('info')
            ->with('Goal completed successfully', \Mockery::on(function ($data) use ($goal) {
                return $data['goal_id'] === $goal->id &&
                       $data['user_id'] === $goal->user_id &&
                       $data['title'] === $goal->title &&
                       isset($data['completed_at']);
            }));

        Log::shouldHaveReceived('warning')
            ->with('Goal completion notification recipient not configured', \Mockery::on(function ($data) use ($goal) {
                return $data['goal_id'] === $goal->id;
            }));
    }

    public function test_goal_completion_updates_timestamps(): void
    {
        // Arrange
        $user = User::factory()->create(['onboarding_completed' => true]);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Act
        $this->actingAs($user)
            ->patch(route('goals.complete', $goal));

        // Assert
        $goal->refresh();
        $this->assertEquals(Goal::STATUS_COMPLETED, $goal->status);
        $this->assertNotNull($goal->completed_at);
        $this->assertTrue($goal->completed_at->isToday());
    }
}