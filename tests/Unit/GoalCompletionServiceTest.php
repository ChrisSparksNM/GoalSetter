<?php

namespace Tests\Unit;

use App\Mail\GoalCompletionMail;
use App\Models\Goal;
use App\Models\User;
use App\Services\GoalCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GoalCompletionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GoalCompletionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GoalCompletionService();
        
        // Set up test email configuration
        Config::set('mail.goal_notification_recipient', 'admin@example.com');
    }

    public function test_complete_goal_updates_status_and_completion_date(): void
    {
        // Arrange
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
            'completed_at' => null,
        ]);

        // Act
        $this->service->completeGoal($goal);

        // Assert
        $goal->refresh();
        $this->assertEquals(Goal::STATUS_COMPLETED, $goal->status);
        $this->assertNotNull($goal->completed_at);
        $this->assertTrue($goal->completed_at->isToday());
    }

    public function test_complete_goal_logs_completion_info(): void
    {
        // Arrange
        Mail::fake();
        Log::shouldReceive('info')
            ->once()
            ->with('Goal completed successfully', \Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Goal completion notification sent successfully', \Mockery::type('array'));

        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Act
        $this->service->completeGoal($goal);

        // Assert - expectations are verified by Mockery
    }

    public function test_complete_goal_handles_notification_errors_gracefully(): void
    {
        // Arrange
        Log::shouldReceive('info')
            ->once()
            ->with('Goal completed successfully', \Mockery::type('array'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to send goal completion notification', \Mockery::type('array'));

        // Force email to fail
        Mail::shouldReceive('to')->andThrow(new \Exception('Email service unavailable'));

        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Act & Assert - should not throw exception
        $this->service->completeGoal($goal);
        
        // Verify goal is still completed even if notification fails
        $goal->refresh();
        $this->assertEquals(Goal::STATUS_COMPLETED, $goal->status);
    }

    public function test_complete_goal_calls_mark_complete_on_goal(): void
    {
        // Arrange
        Mail::fake();
        
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
            'completed_at' => null,
        ]);

        // Act
        $this->service->completeGoal($goal);

        // Assert
        $goal->refresh();
        $this->assertEquals(Goal::STATUS_COMPLETED, $goal->status);
        $this->assertNotNull($goal->completed_at);
        $this->assertTrue($goal->completed_at->isToday());
    }

    public function test_complete_goal_sends_email_notification(): void
    {
        // Arrange
        Mail::fake();
        
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        // Act
        $this->service->completeGoal($goal);

        // Assert
        Mail::assertSent(GoalCompletionMail::class, function ($mail) use ($goal) {
            return $mail->goal->id === $goal->id &&
                   $mail->hasTo('admin@example.com');
        });
    }
}