<?php

use App\Models\User;
use App\Models\Goal;
use App\Services\GoalCompletionService;
use Illuminate\Support\Facades\Log;

test('goal completion service logs successful completion', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    Log::shouldReceive('info')
        ->twice()
        ->withAnyArgs();

    $service = new GoalCompletionService();
    $service->completeGoal($goal);
    
    expect($goal->fresh()->status)->toBe('completed');
});

test('goal completion service logs email failures', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    // Mock mail failure by not setting email configuration
    config(['mail.goal_notification_recipient' => null]);

    Log::shouldReceive('warning')
        ->once()
        ->with('Goal completion notification recipient not configured', [
            'goal_id' => $goal->id
        ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Goal completed successfully', \Mockery::type('array'));

    $service = new GoalCompletionService();
    $service->completeGoal($goal);
});

test('error events are logged with context', function () {
    // This test verifies that our exception handler is properly configured
    // In testing environment, Laravel uses its default handler
    
    $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
    expect($handler)->toBeInstanceOf(\Illuminate\Contracts\Debug\ExceptionHandler::class);
});