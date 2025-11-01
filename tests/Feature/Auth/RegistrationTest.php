<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register and are redirected to login', function () {
    Event::fake();
    
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // User should not be authenticated after registration
    $this->assertGuest();
    
    // Should redirect to login with success message
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', 'Registration successful! Please check your email to verify your account before logging in.');
    
    // User should be created in database
    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'onboarding_completed' => false,
        'email_verified_at' => null,
    ]);
    
    // Registered event should be fired
    Event::assertDispatched(Registered::class);
});

test('registration requires valid data', function () {
    $response = $this->post('/register', []);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
});

test('registration requires unique email', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('registration requires password confirmation', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors(['password']);
});

test('unverified users cannot login', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email']);
    expect($response->getSession()->get('errors')->first('email'))
        ->toContain('verify your email address');
});

test('verified users can login', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'onboarding_completed' => false, // New user hasn't completed onboarding
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('onboarding.video')); // New users go to onboarding first
});

test('users can resend verification email from login page', function () {
    Notification::fake();
    
    $user = User::factory()->unverified()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->post('/email/verification-notification-login', [
        'email' => 'test@example.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'A new verification link has been sent to your email address.');
});

test('resend verification email requires valid email', function () {
    $response = $this->post('/email/verification-notification-login', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('resend verification email shows message for already verified users', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->post('/email/verification-notification-login', [
        'email' => 'test@example.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'Your email is already verified. You can log in now.');
});
