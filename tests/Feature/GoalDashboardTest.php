<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
    }

    public function test_authenticated_user_can_view_goals_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertViewIs('goals.index');
        $response->assertViewHas(['goals', 'statusFilter']);
    }

    public function test_unauthenticated_user_cannot_access_goals_dashboard(): void
    {
        $response = $this->get(route('goals.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_completed_onboarding_cannot_access_goals_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('goals.index'));

        $response->assertRedirect(route('onboarding.video'));
    }

    public function test_goals_dashboard_displays_user_goals_only(): void
    {
        // Create goals for the authenticated user
        $userGoals = Goal::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create goals for another user
        $otherUser = User::factory()->create();
        Goal::factory()->count(2)->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.index'));

        $response->assertStatus(200);
        
        // Check that only user's goals are displayed
        $viewGoals = $response->viewData('goals');
        $this->assertCount(3, $viewGoals);
        
        foreach ($viewGoals as $goal) {
            $this->assertEquals($this->user->id, $goal->user_id);
        }
    }

    public function test_goals_dashboard_shows_all_goals_by_default(): void
    {
        Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);
        
        Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => Goal::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.index'));

        $response->assertStatus(200);
        
        $viewGoals = $response->viewData('goals');
        $statusFilter = $response->viewData('statusFilter');
        
        $this->assertCount(2, $viewGoals);
        $this->assertEquals('all', $statusFilter);
    }

    public function test_goals_dashboard_filters_by_active_status(): void
    {
        Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);
        
        Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => Goal::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.index', ['status' => 'active']));

        $response->assertStatus(200);
        
        $viewGoals = $response->viewData('goals');
        $statusFilter = $response->viewData('statusFilter');
        
        $this->assertCount(1, $viewGoals);
        $this->assertEquals('active', $statusFilter);
        $this->assertEquals(Goal::STATUS_ACTIVE, $viewGoals->first()->status);
    }

    public function test_goals_dashboard_filters_by_completed_status(): void
    {
        Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);
        
        Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => Goal::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.index', ['status' => 'completed']));

        $response->assertStatus(200);
        
        $viewGoals = $response->viewData('goals');
        $statusFilter = $response->viewData('statusFilter');
        
        $this->assertCount(1, $viewGoals);
        $this->assertEquals('completed', $statusFilter);
        $this->assertEquals(Goal::STATUS_COMPLETED, $viewGoals->first()->status);
    }

    public function test_goals_dashboard_ignores_invalid_status_filter(): void
    {
        Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.index', ['status' => 'invalid']));

        $response->assertStatus(200);
        
        $viewGoals = $response->viewData('goals');
        $statusFilter = $response->viewData('statusFilter');
        
        $this->assertCount(1, $viewGoals);
        $this->assertEquals('invalid', $statusFilter); // Filter value is preserved but ignored
    }

    public function test_goals_dashboard_displays_goals_in_latest_order(): void
    {
        $firstGoal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);
        
        $secondGoal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);
        
        $thirdGoal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.index'));

        $response->assertStatus(200);
        
        $viewGoals = $response->viewData('goals');
        
        // Goals should be ordered by latest first
        $this->assertEquals($thirdGoal->id, $viewGoals->first()->id);
        $this->assertEquals($firstGoal->id, $viewGoals->last()->id);
    }

    public function test_goals_dashboard_shows_empty_state_when_no_goals(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertSee('No goals yet');
        $response->assertSee('Create Your First Goal');
    }

    public function test_goals_dashboard_shows_filtered_empty_state(): void
    {
        Goal::factory()->create([
            'user_id' => $this->user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.index', ['status' => 'completed']));

        $response->assertStatus(200);
        $response->assertSee('No completed goals found');
        $response->assertSee('View all goals');
    }

    public function test_user_can_view_individual_goal_details(): void
    {
        $goal = Goal::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.show', $goal));

        $response->assertStatus(200);
        $response->assertViewIs('goals.show');
        $response->assertViewHas('goal', $goal);
        $response->assertSee($goal->title);
    }

    public function test_user_cannot_view_other_users_goals(): void
    {
        $otherUser = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('goals.show', $goal));

        $response->assertStatus(403);
    }

    public function test_goals_dashboard_displays_success_message_after_goal_creation(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['success' => 'Goal created successfully!'])
            ->get(route('goals.index'));

        $response->assertStatus(200);
        $response->assertSee('Goal created successfully!');
    }
}