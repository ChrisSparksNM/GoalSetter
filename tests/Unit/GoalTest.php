<?php

namespace Tests\Unit;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_can_be_created_with_valid_data(): void
    {
        $user = User::factory()->create();
        
        $goalData = [
            'title' => 'Learn Laravel',
            'description' => 'Complete Laravel course',
            'end_date' => Carbon::tomorrow(),
            'status' => Goal::STATUS_ACTIVE,
        ];

        $goal = $user->goals()->create($goalData);

        $this->assertInstanceOf(Goal::class, $goal);
        $this->assertEquals('Learn Laravel', $goal->title);
        $this->assertEquals('Complete Laravel course', $goal->description);
        $this->assertEquals(Goal::STATUS_ACTIVE, $goal->status);
        $this->assertEquals($user->id, $goal->user_id);
    }

    public function test_goal_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $goal->user);
        $this->assertEquals($user->id, $goal->user->id);
    }

    public function test_user_has_many_goals(): void
    {
        $user = User::factory()->create();
        $goals = Goal::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->goals);
        $this->assertInstanceOf(Goal::class, $user->goals->first());
    }

    public function test_goal_can_be_marked_complete(): void
    {
        $goal = Goal::factory()->create([
            'status' => Goal::STATUS_ACTIVE,
            'completed_at' => null,
        ]);

        $goal->markComplete();

        $this->assertEquals(Goal::STATUS_COMPLETED, $goal->status);
        $this->assertNotNull($goal->completed_at);
    }

    public function test_goal_is_overdue_when_past_end_date_and_active(): void
    {
        $goal = Goal::factory()->create([
            'end_date' => Carbon::yesterday(),
            'status' => Goal::STATUS_ACTIVE,
        ]);

        $this->assertTrue($goal->isOverdue());
    }

    public function test_goal_is_not_overdue_when_completed(): void
    {
        $goal = Goal::factory()->create([
            'end_date' => Carbon::yesterday(),
            'status' => Goal::STATUS_COMPLETED,
        ]);

        $this->assertFalse($goal->isOverdue());
    }

    public function test_goal_is_not_overdue_when_future_end_date(): void
    {
        $goal = Goal::factory()->create([
            'end_date' => Carbon::tomorrow(),
            'status' => Goal::STATUS_ACTIVE,
        ]);

        $this->assertFalse($goal->isOverdue());
    }

    public function test_get_days_remaining_returns_correct_value(): void
    {
        $goal = Goal::factory()->create([
            'end_date' => Carbon::now()->addDays(5),
            'status' => Goal::STATUS_ACTIVE,
        ]);

        $this->assertEquals(5, $goal->getDaysRemaining());
    }

    public function test_get_days_remaining_returns_zero_for_completed_goal(): void
    {
        $goal = Goal::factory()->create([
            'end_date' => Carbon::now()->addDays(5),
            'status' => Goal::STATUS_COMPLETED,
        ]);

        $this->assertEquals(0, $goal->getDaysRemaining());
    }

    public function test_get_days_remaining_returns_zero_for_past_date(): void
    {
        $goal = Goal::factory()->create([
            'end_date' => Carbon::yesterday(),
            'status' => Goal::STATUS_ACTIVE,
        ]);

        $this->assertEquals(0, $goal->getDaysRemaining());
    }

    public function test_active_scope_returns_only_active_goals(): void
    {
        $user = User::factory()->create();
        
        Goal::factory()->create(['user_id' => $user->id, 'status' => Goal::STATUS_ACTIVE]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => Goal::STATUS_COMPLETED]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => Goal::STATUS_CANCELLED]);

        $activeGoals = Goal::active()->get();

        $this->assertCount(1, $activeGoals);
        $this->assertEquals(Goal::STATUS_ACTIVE, $activeGoals->first()->status);
    }

    public function test_completed_scope_returns_only_completed_goals(): void
    {
        $user = User::factory()->create();
        
        Goal::factory()->create(['user_id' => $user->id, 'status' => Goal::STATUS_ACTIVE]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => Goal::STATUS_COMPLETED]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => Goal::STATUS_CANCELLED]);

        $completedGoals = Goal::completed()->get();

        $this->assertCount(1, $completedGoals);
        $this->assertEquals(Goal::STATUS_COMPLETED, $completedGoals->first()->status);
    }
}