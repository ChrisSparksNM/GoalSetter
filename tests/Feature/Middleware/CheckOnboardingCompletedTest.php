<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\CheckOnboardingCompleted;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CheckOnboardingCompletedTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_redirects_user_without_completed_onboarding(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => false,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $middleware = new CheckOnboardingCompleted();
        
        $response = $middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(str_contains($response->headers->get('Location'), 'onboarding/video'));
    }

    public function test_middleware_allows_user_with_completed_onboarding(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $middleware = new CheckOnboardingCompleted();
        
        $response = $middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_middleware_allows_unauthenticated_users(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(function () {
            return null;
        });

        $middleware = new CheckOnboardingCompleted();
        
        $response = $middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }
}