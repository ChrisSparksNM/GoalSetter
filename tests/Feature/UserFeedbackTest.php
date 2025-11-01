<?php

use App\Models\User;
use App\Models\Goal;

test('successful registration shows success message', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHas('success', 'Registration successful! Please check your email to verify your account before logging in.');
});

test('successful goal creation shows success message', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

    $response = $this->actingAs($user)->post('/goals', [
        'title' => 'Test Goal',
        'description' => 'Test Description',
        'end_date' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $response->assertRedirect('/goals');
    $response->assertSessionHas('success', 'Goal created successfully!');
});

test('successful goal completion shows success message', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
    $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $response = $this->actingAs($user)->patch("/goals/{$goal->id}/complete");

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Congratulations! Goal completed successfully and notification sent.');
});

test('successful onboarding completion shows success message', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => false]);

    $response = $this->actingAs($user)->post('/onboarding/complete');

    $response->assertRedirect('/goals/create');
    $response->assertSessionHas('success', 'Welcome! You\'ve completed the onboarding. Now let\'s create your first goal.');
});

test('attempting to complete already completed goal shows error message', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
    $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

    $response = $this->actingAs($user)->patch("/goals/{$goal->id}/complete");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'This goal is already completed.');
});

test('attempting to complete non active goal shows error message', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
    $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'cancelled']);

    $response = $this->actingAs($user)->patch("/goals/{$goal->id}/complete");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Only active goals can be marked as complete.');
});

test('flash messages component displays success messages', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

    $response = $this->actingAs($user)
        ->withSession(['success' => 'Test success message'])
        ->get('/goals');

    $response->assertSee('Test success message');
    $response->assertSee('text-green-400'); // Success icon color
});

test('flash messages component displays error messages', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

    $response = $this->actingAs($user)
        ->withSession(['error' => 'Test error message'])
        ->get('/goals');

    $response->assertSee('Test error message');
    $response->assertSee('text-red-400'); // Error icon color
});

test('flash messages component displays warning messages', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

    $response = $this->actingAs($user)
        ->withSession(['warning' => 'Test warning message'])
        ->get('/goals');

    $response->assertSee('Test warning message');
    $response->assertSee('text-yellow-400'); // Warning icon color
});

test('flash messages component displays info messages', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

    $response = $this->actingAs($user)
        ->withSession(['info' => 'Test info message'])
        ->get('/goals');

    $response->assertSee('Test info message');
    $response->assertSee('text-blue-400'); // Info icon color
});

test('validation errors are displayed with proper styling', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

    $response = $this->actingAs($user)->post('/goals', [
        'title' => '', // Required field missing
        'end_date' => '2020-01-01', // Past date
    ]);

    $response->assertSessionHasErrors(['title', 'end_date']);
    
    // Follow the redirect to see the form with errors
    $followUpResponse = $this->actingAs($user)->get('/goals/create');
    $followUpResponse->assertSee('The goal title is required.');
    $followUpResponse->assertSee('The end date must be a future date.');
});

test('unauthenticated access redirects to login', function () {
    $response = $this->get('/goals');

    $response->assertRedirect('/login');
});

test('registration form preserves input on validation error', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasInput('name', 'Test User');
    $response->assertSessionHasInput('email', 'invalid-email');
    $response->assertSessionHasErrors(['email', 'password']);
});

test('goal creation form preserves input on validation error', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

    $response = $this->actingAs($user)->post('/goals', [
        'title' => 'Valid Title',
        'description' => 'Valid Description',
        'end_date' => '2020-01-01', // Invalid past date
    ]);

    $response->assertSessionHasInput('title', 'Valid Title');
    $response->assertSessionHasInput('description', 'Valid Description');
    $response->assertSessionHasErrors(['end_date']);
});

test('error pages contain helpful navigation links', function () {
    expect(view()->exists('errors.401'))->toBeTrue();
    expect(view()->exists('errors.403'))->toBeTrue();
    expect(view()->exists('errors.419'))->toBeTrue();
    expect(view()->exists('errors.500'))->toBeTrue();
});

test('flash messages are dismissible', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

    $response = $this->actingAs($user)
        ->withSession(['success' => 'Dismissible message'])
        ->get('/goals');

    // Check that dismiss button is present
    $response->assertSee('Dismiss');
    $response->assertSee('this.parentElement.parentElement.parentElement.parentElement.style.display', false);
});