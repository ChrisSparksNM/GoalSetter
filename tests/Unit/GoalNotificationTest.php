<?php

namespace Tests\Unit;

use App\Models\Goal;
use App\Models\GoalNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_notification_can_be_created_with_valid_data(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        
        $notificationData = [
            'goal_id' => $goal->id,
            'recipient_email' => 'admin@example.com',
            'status' => GoalNotification::STATUS_PENDING,
        ];

        $notification = GoalNotification::create($notificationData);

        $this->assertInstanceOf(GoalNotification::class, $notification);
        $this->assertEquals($goal->id, $notification->goal_id);
        $this->assertEquals('admin@example.com', $notification->recipient_email);
        $this->assertEquals(GoalNotification::STATUS_PENDING, $notification->status);
    }

    public function test_goal_notification_belongs_to_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $notification = GoalNotification::factory()->create(['goal_id' => $goal->id]);

        $this->assertInstanceOf(Goal::class, $notification->goal);
        $this->assertEquals($goal->id, $notification->goal->id);
    }

    public function test_mark_as_sent_updates_status_and_timestamp(): void
    {
        $notification = GoalNotification::factory()->create([
            'status' => GoalNotification::STATUS_PENDING,
            'sent_at' => null,
        ]);

        $this->assertEquals(GoalNotification::STATUS_PENDING, $notification->status);
        $this->assertNull($notification->sent_at);

        $notification->markAsSent();

        $this->assertEquals(GoalNotification::STATUS_SENT, $notification->status);
        $this->assertNotNull($notification->sent_at);
        $this->assertTrue($notification->sent_at->isToday());
    }

    public function test_mark_as_failed_updates_status(): void
    {
        $notification = GoalNotification::factory()->create([
            'status' => GoalNotification::STATUS_PENDING,
        ]);

        $this->assertEquals(GoalNotification::STATUS_PENDING, $notification->status);

        $notification->markAsFailed();

        $this->assertEquals(GoalNotification::STATUS_FAILED, $notification->status);
    }

    public function test_notification_casts_are_properly_configured(): void
    {
        $notification = GoalNotification::factory()->create([
            'sent_at' => now(),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $notification->sent_at);
    }

    public function test_notification_fillable_attributes(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        
        $notificationData = [
            'goal_id' => $goal->id,
            'recipient_email' => 'test@example.com',
            'sent_at' => now(),
            'status' => GoalNotification::STATUS_SENT,
        ];

        $notification = new GoalNotification();
        $notification->fill($notificationData);

        $this->assertEquals($goal->id, $notification->goal_id);
        $this->assertEquals('test@example.com', $notification->recipient_email);
        $this->assertEquals(GoalNotification::STATUS_SENT, $notification->status);
        $this->assertNotNull($notification->sent_at);
    }

    public function test_notification_status_constants(): void
    {
        $this->assertEquals('pending', GoalNotification::STATUS_PENDING);
        $this->assertEquals('sent', GoalNotification::STATUS_SENT);
        $this->assertEquals('failed', GoalNotification::STATUS_FAILED);
    }

    public function test_notification_factory_creates_valid_instance(): void
    {
        $notification = GoalNotification::factory()->create();

        $this->assertInstanceOf(GoalNotification::class, $notification);
        $this->assertNotNull($notification->goal_id);
        $this->assertNotNull($notification->recipient_email);
        $this->assertContains($notification->status, [
            GoalNotification::STATUS_PENDING,
            GoalNotification::STATUS_SENT,
            GoalNotification::STATUS_FAILED,
        ]);
    }

    public function test_mark_as_sent_persists_to_database(): void
    {
        $notification = GoalNotification::factory()->create([
            'status' => GoalNotification::STATUS_PENDING,
            'sent_at' => null,
        ]);

        $notification->markAsSent();
        $notification->refresh();

        $this->assertEquals(GoalNotification::STATUS_SENT, $notification->status);
        $this->assertNotNull($notification->sent_at);
    }

    public function test_mark_as_failed_persists_to_database(): void
    {
        $notification = GoalNotification::factory()->create([
            'status' => GoalNotification::STATUS_PENDING,
        ]);

        $notification->markAsFailed();
        $notification->refresh();

        $this->assertEquals(GoalNotification::STATUS_FAILED, $notification->status);
    }
}