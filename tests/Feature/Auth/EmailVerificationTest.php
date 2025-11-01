<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified and user is redirected to login', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    
    // User should be logged out and redirected to login
    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', 'Email verified successfully! You can now log in to your account.');
});

test('already verified email redirects to login with message', function () {
    $user = User::factory()->create(); // Already verified

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', 'Your email is already verified. You can now log in.');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('complete registration and verification workflow', function () {
    Event::fake();
    
    // Step 1: Register new user
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
    
    $response->assertRedirect(route('login'));
    $this->assertGuest();
    
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasVerifiedEmail())->toBeFalse();
    expect($user->onboarding_completed)->toBeFalse();
    
    // Step 2: Try to login without verification (should fail)
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    
    $this->assertGuest();
    $response->assertSessionHasErrors(['email']);
    
    // Step 3: Verify email
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );
    
    $response = $this->actingAs($user)->get($verificationUrl);
    
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertGuest(); // Should be logged out after verification
    $response->assertRedirect(route('login'));
    
    // Step 4: Login after verification (should redirect to onboarding for new users)
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    
    $this->assertAuthenticated();
    $response->assertRedirect(route('onboarding.video')); // New users go to onboarding first
});
