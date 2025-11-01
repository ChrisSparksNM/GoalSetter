<?php

namespace Tests\Unit;

use App\Mail\GoalCompletionMail;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalCompletionMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_completion_mail_has_correct_subject(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Learn Laravel',
            'status' => 'completed',
            'completed_at' => now()
        ]);

        $mail = new GoalCompletionMail($goal);
        $envelope = $mail->envelope();

        $this->assertEquals('Goal Completed: Learn Laravel', $envelope->subject);
    }

    public function test_goal_completion_mail_uses_correct_view(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'completed_at' => now()
        ]);

        $mail = new GoalCompletionMail($goal);
        $content = $mail->content();

        $this->assertEquals('emails.goal-completion', $content->view);
    }

    public function test_goal_completion_mail_contains_goal_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);
        
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Complete Project',
            'description' => 'Finish the Laravel project',
            'end_date' => now()->addDays(30),
            'status' => 'completed',
            'completed_at' => now()
        ]);

        $mail = new GoalCompletionMail($goal);
        
        // Test that the mail object has access to the goal
        $this->assertEquals($goal->id, $mail->goal->id);
        $this->assertEquals($goal->title, $mail->goal->title);
        $this->assertEquals($goal->user->name, $mail->goal->user->name);
    }

    public function test_goal_completion_mail_renders_correctly(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Goal',
            'description' => 'This is a test goal',
            'end_date' => now()->addDays(7),
            'status' => 'completed',
            'completed_at' => now()
        ]);

        $mail = new GoalCompletionMail($goal);
        $rendered = $mail->render();

        // Check that key information is present in the rendered email
        $this->assertStringContainsString('Goal Completed!', $rendered);
        $this->assertStringContainsString($user->name, $rendered);
        $this->assertStringContainsString($goal->title, $rendered);
        $this->assertStringContainsString($goal->description, $rendered);
        $this->assertStringContainsString($user->email, $rendered);
        $this->assertStringContainsString('🎉', $rendered);
    }
}