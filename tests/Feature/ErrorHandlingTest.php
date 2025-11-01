<?php

use App\Models\User;
use App\Models\Goal;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Clear logs before each test
    if (file_exists(storage_path('logs/laravel.log'))) {
        file_put_contents(storage_path('logs/laravel.log'), '');
    }
});

test('registration validation errors are displayed properly', function () {
    $response = $this->post('/register', [
        'name' => '',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'different',
    ]);

    $response->assertSessionHasErrors([
        'name' => 'Please enter your full name.',
        'email' => 'Please enter a valid email address.',
        'password' => 'The password confirmation does not match.',
    ]);
});

test('registration with existing email shows proper error', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors([
        'email' => 'This email address is already registered. Please use a different email or try logging in.',
    ]);
});

test('login validation errors are displayed properly', function () {
    $response = $this->post('/login', [
        'email' => '',
        'password' => '',
    ]);

    $response->assertSessionHasErrors([
        'email' => 'Please enter your email address.',
        'password' => 'Please enter your password.',
    ]);
});

test('login with invalid credentials shows proper error', function () {
    $response = $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors([
        'email' => 'These credentials do not match our records. Please check your email and password and try again.',
    ]);
});

test('login with unverified email shows proper error', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors([
        'email' => 'You must verify your email address before logging in. Please check your email for a verification link.',
    ]);
});

test('goal creation validation errors are displayed properly', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

    $response = $this->actingAs($user)->post('/goals', [
        'title' => '',
        'description' => str_repeat('a', 1001), // Too long
        'end_date' => '2020-01-01', // Past date
    ]);

    $response->assertSessionHasErrors([
        'title' => 'The goal title is required.',
        'description' => 'The description may not be greater than 1000 characters.',
        'end_date' => 'The end date must be a future date.',
    ]);
});

test('goal completion error is handled gracefully', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
    $goal = Goal::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

    $response = $this->actingAs($user)->patch("/goals/{$goal->id}/complete");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'This goal is already completed.');
});

test('unauthorized goal access returns 403', function () {
    $user1 = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
    $user2 = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
    $goal = Goal::factory()->create(['user_id' => $user1->id]);

    $response = $this->actingAs($user2)->get("/goals/{$goal->id}");

    $response->assertStatus(403);
});

test('unauthenticated access redirects to login', function () {
    $response = $this->get('/goals');

    $response->assertRedirect('/login');
});

test('flash messages are displayed correctly', function () {
    $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

    $response = $this->actingAs($user)->post('/goals', [
        'title' => 'Test Goal',
        'description' => 'Test Description',
        'end_date' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $response->assertRedirect('/goals');
    $response->assertSessionHas('success', 'Goal created successfully!');
});

test('validation errors preserve input data', function () {
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

test('error pages exist', function () {
    expect(view()->exists('errors.401'))->toBeTrue();
    expect(view()->exists('errors.403'))->toBeTrue();
    expect(view()->exists('errors.419'))->toBeTrue();
    expect(view()->exists('errors.500'))->toBeTrue();
});