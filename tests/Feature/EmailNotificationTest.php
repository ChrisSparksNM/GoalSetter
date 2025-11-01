<?php

namespace Tests\Feature;

use App\Mail\GoalCompletionMail;
use App\Models\Goal;
use App\Models\User;
use App\Services\GoalCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test email configuration
        Config::set('mail.goal_notification_recipient', 'admin@example.com');
    }

    public function test_email_is_sent_when_goal_is_completed(): void
    {
        Mail::fake();

        $user = User::factory()->create(['name' => 'John Doe']);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Learn Laravel',
            'description' => 'Complete Laravel tutorial',
            'end_date' => now()->addDays(30),
            'status' => 'active'
        ]);

        $service = new GoalCompletionService();
        $service->completeGoal($goal);

        // Assert email was sent
        Mail::assertSent(GoalCompletionMail::class, function ($mail) use ($goal) {
            return $mail->goal->id === $goal->id;
        });

        // Assert email was sent to correct recipient
        Mail::assertSent(GoalCompletionMail::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });
    }

    public function test_email_contains_correct_goal_information(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);
        
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Complete Project',
            'description' => 'Finish the Laravel project',
            'end_date' => now()->addDays(7),
            'status' => 'active'
        ]);

        $service = new GoalCompletionService();
        $service->completeGoal($goal);

        Mail::assertSent(GoalCompletionMail::class, function ($mail) use ($goal, $user) {
            $mailGoal = $mail->goal;
            return $mailGoal->title === $goal->title &&
                   $mailGoal->description === $goal->description &&
                   $mailGoal->user->name === $user->name &&
                   $mailGoal->user->email === $user->email &&
                   $mailGoal->status === 'completed' &&
                   $mailGoal->completed_at !== null;
        });
    }

    public function test_goal_completion_succeeds_even_if_email_fails(): void
    {
        // Simulate email failure by using invalid configuration
        Config::set('mail.default', 'array');
        Mail::shouldReceive('to')->andThrow(new \Exception('Email service unavailable'));

        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        $service = new GoalCompletionService();
        
        // This should not throw an exception
        $service->completeGoal($goal);

        // Goal should still be marked as completed
        $goal->refresh();
        $this->assertEquals('completed', $goal->status);
        $this->assertNotNull($goal->completed_at);
    }

    public function test_email_failure_is_logged(): void
    {
        Log::spy();
        
        // Force email to fail
        Mail::shouldReceive('to')->andThrow(new \Exception('SMTP connection failed'));

        $user = User::factory()->create(['name' => 'Test User']);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Goal',
            'status' => 'active'
        ]);

        $service = new GoalCompletionService();
        $service->completeGoal($goal);

        // Assert error was logged
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Failed to send goal completion notification', \Mockery::on(function ($context) use ($goal) {
                return $context['goal_id'] === $goal->id &&
                       $context['user_name'] === 'Test User' &&
                       $context['goal_title'] === 'Test Goal' &&
                       isset($context['error']) &&
                       isset($context['trace']);
            }));
    }

    public function test_no_email_sent_when_recipient_not_configured(): void
    {
        Mail::fake();
        Log::spy();
        
        // Remove email recipient configuration
        Config::set('mail.goal_notification_recipient', null);

        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        $service = new GoalCompletionService();
        $service->completeGoal($goal);

        // No email should be sent
        Mail::assertNothingSent();

        // Warning should be logged
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Goal completion notification recipient not configured', [
                'goal_id' => $goal->id
            ]);
    }

    public function test_successful_email_sending_is_logged(): void
    {
        Mail::fake();
        Log::spy();

        $user = User::factory()->create(['name' => 'Success User']);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Success Goal',
            'status' => 'active'
        ]);

        $service = new GoalCompletionService();
        $service->completeGoal($goal);

        // Assert success was logged
        Log::shouldHaveReceived('info')
            ->with('Goal completion notification sent successfully', \Mockery::on(function ($context) use ($goal) {
                return $context['goal_id'] === $goal->id &&
                       $context['user_name'] === 'Success User' &&
                       $context['goal_title'] === 'Success Goal' &&
                       $context['recipient'] === 'admin@example.com' &&
                       isset($context['completed_at']);
            }));
    }

    public function test_email_template_renders_with_all_required_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Template Test User',
            'email' => 'template@example.com'
        ]);
        
        $completionDate = now();
        $endDate = now()->addDays(14);
        
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Template Test Goal',
            'description' => 'This goal tests the email template',
            'end_date' => $endDate,
            'status' => 'completed',
            'completed_at' => $completionDate
        ]);

        $mail = new GoalCompletionMail($goal);
        $rendered = $mail->render();

        // Verify all required data is present
        $this->assertStringContainsString('Template Test User', $rendered);
        $this->assertStringContainsString('Template Test Goal', $rendered);
        $this->assertStringContainsString('This goal tests the email template', $rendered);
        $this->assertStringContainsString('template@example.com', $rendered);
        $this->assertStringContainsString($endDate->format('F j, Y'), $rendered);
        $this->assertStringContainsString($completionDate->format('F j, Y'), $rendered);
        $this->assertStringContainsString('Goal Completed!', $rendered);
        $this->assertStringContainsString('🎉', $rendered);
    }
}