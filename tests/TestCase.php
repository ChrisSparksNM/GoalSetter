<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test-specific configurations
        $this->setupTestConfiguration();
        
        // Clear any cached data
        $this->clearApplicationCache();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Clean up any test artifacts
        $this->cleanupTestArtifacts();
        
        parent::tearDown();
    }

    /**
     * Setup test-specific configuration.
     */
    protected function setupTestConfiguration(): void
    {
        // Set test-specific config values
        config([
            'mail.default' => 'array',
            'queue.default' => 'sync',
            'session.driver' => 'array',
            'cache.default' => 'array',
        ]);
    }

    /**
     * Clear application cache for clean test state.
     */
    protected function clearApplicationCache(): void
    {
        if (app()->bound('cache')) {
            app('cache')->flush();
        }
    }

    /**
     * Clean up test artifacts.
     */
    protected function cleanupTestArtifacts(): void
    {
        // Clean up any temporary files or resources created during tests
        if (file_exists(storage_path('logs/laravel.log'))) {
            file_put_contents(storage_path('logs/laravel.log'), '');
        }
    }

    /**
     * Seed the test database with comprehensive test data.
     */
    protected function seedTestDatabase(): void
    {
        $seeder = new TestDatabaseSeeder();
        $seeder->run();
    }

    /**
     * Create a verified user with completed onboarding for testing.
     */
    protected function createCompleteUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ], $attributes));
    }

    /**
     * Create an unverified user for testing email verification flows.
     */
    protected function createUnverifiedUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create(array_merge([
            'email_verified_at' => null,
            'onboarding_completed' => false,
        ], $attributes));
    }

    /**
     * Create a user with incomplete onboarding for testing onboarding flows.
     */
    protected function createOnboardingUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ], $attributes));
    }

    /**
     * Assert that the database has the expected number of records.
     */
    protected function assertDatabaseRecordCount(string $table, int $count): void
    {
        $actual = DB::table($table)->count();
        $this->assertEquals(
            $count,
            $actual,
            "Expected {$count} records in {$table} table, but found {$actual}."
        );
    }

    /**
     * Assert that a model exists in the database with the given attributes.
     */
    protected function assertDatabaseHasModel(string $model, array $attributes): void
    {
        $this->assertDatabaseHas((new $model)->getTable(), $attributes);
    }

    /**
     * Assert that a model does not exist in the database with the given attributes.
     */
    protected function assertDatabaseMissingModel(string $model, array $attributes): void
    {
        $this->assertDatabaseMissing((new $model)->getTable(), $attributes);
    }

    /**
     * Get the test database connection.
     */
    protected function getTestDatabaseConnection(): \Illuminate\Database\Connection
    {
        return DB::connection(config('database.default'));
    }

    /**
     * Execute a database transaction for testing.
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Assert that an email was sent with specific content.
     */
    protected function assertEmailSentWithContent(string $mailableClass, array $expectedContent): void
    {
        \Illuminate\Support\Facades\Mail::assertSent($mailableClass, function ($mail) use ($expectedContent) {
            $rendered = $mail->render();
            
            foreach ($expectedContent as $content) {
                if (!str_contains($rendered, $content)) {
                    return false;
                }
            }
            
            return true;
        });
    }

    /**
     * Assert that a log entry was created with specific content.
     */
    protected function assertLogContains(string $level, string $message): void
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            $this->fail('Log file does not exist');
        }
        
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString($level, $logContent);
        $this->assertStringContainsString($message, $logContent);
    }

    /**
     * Create test goals with specific statuses for a user.
     */
    protected function createGoalsForUser(\App\Models\User $user, array $statuses = []): \Illuminate\Database\Eloquent\Collection
    {
        $goals = collect();
        
        foreach ($statuses as $status) {
            $goalData = ['user_id' => $user->id, 'status' => $status];
            
            if ($status === \App\Models\Goal::STATUS_COMPLETED) {
                $goalData['completed_at'] = now()->subDay();
            }
            
            $goals->push(\App\Models\Goal::factory()->create($goalData));
        }
        
        return $goals;
    }

    /**
     * Assert that a response contains validation errors for specific fields.
     */
    protected function assertValidationErrors(\Illuminate\Testing\TestResponse $response, array $fields): void
    {
        $response->assertSessionHasErrors($fields);
        
        foreach ($fields as $field) {
            $this->assertNotEmpty(session('errors')->get($field));
        }
    }

    /**
     * Assert that a response redirects to a specific route with parameters.
     */
    protected function assertRedirectsToRoute(\Illuminate\Testing\TestResponse $response, string $route, array $parameters = []): void
    {
        $expectedUrl = route($route, $parameters);
        $response->assertRedirect($expectedUrl);
    }

    /**
     * Simulate time passage for testing time-dependent functionality.
     */
    protected function travelInTime(\DateTimeInterface $date): void
    {
        $this->travel($date);
    }

    /**
     * Reset time after time travel.
     */
    protected function resetTime(): void
    {
        $this->travelBack();
    }

    /**
     * Create a mock video file for testing video functionality.
     */
    protected function createMockVideoFile(): string
    {
        $videoPath = storage_path('app/public/videos/setting-smart-goals.mp4');
        
        if (!is_dir(dirname($videoPath))) {
            mkdir(dirname($videoPath), 0755, true);
        }
        
        file_put_contents($videoPath, 'mock video content');
        
        return $videoPath;
    }

    /**
     * Clean up mock video files.
     */
    protected function cleanupMockVideoFiles(): void
    {
        $videoPath = storage_path('app/public/videos/setting-smart-goals.mp4');
        
        if (file_exists($videoPath)) {
            unlink($videoPath);
        }
    }
}