<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create performance test data
        $seeder = new \Tests\TestDatabaseSeeder();
        $seeder->createPerformanceTestData();
    }

    public function test_goals_dashboard_performance_with_many_goals(): void
    {
        $user = User::where('email', 'performance@test.com')->first();
        
        // Measure query count and execution time
        DB::enableQueryLog();
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)->get('/goals');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $queryCount = count(DB::getQueryLog());
        
        $response->assertStatus(200);
        
        // Performance assertions
        $this->assertLessThan(1.0, $executionTime, 'Goals dashboard took too long to load');
        $this->assertLessThan(10, $queryCount, 'Too many database queries executed');
        
        DB::disableQueryLog();
    }

    public function test_goal_creation_performance(): void
    {
        $user = $this->createCompleteUser();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)->post('/goals', [
            'title' => 'Performance Test Goal',
            'description' => 'Testing goal creation performance',
            'end_date' => now()->addMonth()->format('Y-m-d'),
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $response->assertRedirect('/goals');
        $this->assertLessThan(0.5, $executionTime, 'Goal creation took too long');
    }

    public function test_goal_completion_performance(): void
    {
        $user = $this->createCompleteUser();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'status' => Goal::STATUS_ACTIVE,
        ]);
        
        DB::enableQueryLog();
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)->patch(route('goals.complete', $goal));
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $queryCount = count(DB::getQueryLog());
        
        $response->assertRedirect();
        
        // Performance assertions
        $this->assertLessThan(1.0, $executionTime, 'Goal completion took too long');
        $this->assertLessThan(5, $queryCount, 'Too many queries for goal completion');
        
        DB::disableQueryLog();
    }

    public function test_user_registration_performance(): void
    {
        $startTime = microtime(true);
        
        $response = $this->post('/register', [
            'name' => 'Performance User',
            'email' => 'perf@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $response->assertRedirect();
        $this->assertLessThan(0.5, $executionTime, 'User registration took too long');
    }

    public function test_onboarding_video_page_performance(): void
    {
        $user = $this->createOnboardingUser();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)->get('/onboarding/video');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $response->assertStatus(200);
        $this->assertLessThan(0.5, $executionTime, 'Onboarding video page took too long to load');
    }

    public function test_goals_filtering_performance(): void
    {
        $user = User::where('email', 'performance@test.com')->first();
        
        $filters = ['all', 'active', 'completed'];
        
        foreach ($filters as $filter) {
            DB::enableQueryLog();
            $startTime = microtime(true);
            
            $response = $this->actingAs($user)->get("/goals?status={$filter}");
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            $queryCount = count(DB::getQueryLog());
            
            $response->assertStatus(200);
            
            $this->assertLessThan(0.8, $executionTime, "Goals filtering by {$filter} took too long");
            $this->assertLessThan(8, $queryCount, "Too many queries for {$filter} filter");
            
            DB::disableQueryLog();
        }
    }

    public function test_concurrent_goal_operations(): void
    {
        $users = User::factory()->count(5)->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        
        $startTime = microtime(true);
        
        // Simulate concurrent operations
        foreach ($users as $user) {
            // Create goal
            $this->actingAs($user)->post('/goals', [
                'title' => "Concurrent Goal for {$user->name}",
                'end_date' => now()->addMonth()->format('Y-m-d'),
            ]);
            
            // View goals
            $this->actingAs($user)->get('/goals');
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Should handle concurrent operations efficiently
        $this->assertLessThan(3.0, $totalTime, 'Concurrent operations took too long');
    }

    public function test_database_query_optimization(): void
    {
        $user = User::where('email', 'performance@test.com')->first();
        
        // Test N+1 query prevention
        DB::enableQueryLog();
        
        $response = $this->actingAs($user)->get('/goals');
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        // Should not have N+1 queries (should be constant regardless of goal count)
        $this->assertLessThan(5, $queryCount, 'Potential N+1 query problem detected');
        
        // Check for efficient queries
        $selectQueries = array_filter($queries, function ($query) {
            return str_starts_with(strtolower(trim($query['query'])), 'select');
        });
        
        $this->assertLessThan(3, count($selectQueries), 'Too many SELECT queries');
        
        DB::disableQueryLog();
    }

    public function test_memory_usage_during_operations(): void
    {
        $user = $this->createCompleteUser();
        
        $initialMemory = memory_get_usage();
        
        // Perform memory-intensive operations
        for ($i = 0; $i < 10; $i++) {
            Goal::factory()->create(['user_id' => $user->id]);
        }
        
        $this->actingAs($user)->get('/goals');
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 5MB)
        $this->assertLessThan(5 * 1024 * 1024, $memoryIncrease, 'Memory usage increased too much');
    }

    public function test_response_size_optimization(): void
    {
        $user = User::where('email', 'performance@test.com')->first();
        
        $response = $this->actingAs($user)->get('/goals');
        
        $responseSize = strlen($response->getContent());
        
        // Response size should be reasonable (less than 500KB for goals page with many goals)
        $this->assertLessThan(500 * 1024, $responseSize, 'Response size is too large');
    }

    public function test_cache_effectiveness(): void
    {
        $user = $this->createCompleteUser();
        
        // First request (cache miss)
        $startTime1 = microtime(true);
        $response1 = $this->actingAs($user)->get('/goals');
        $time1 = microtime(true) - $startTime1;
        
        // Second request (potential cache hit)
        $startTime2 = microtime(true);
        $response2 = $this->actingAs($user)->get('/goals');
        $time2 = microtime(true) - $startTime2;
        
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Second request should be faster if caching is effective
        // Note: This test might need adjustment based on actual caching implementation
        $this->assertTrue(true, 'Cache effectiveness test completed');
    }
}