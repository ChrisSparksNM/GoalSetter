<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Middleware is applied in routes, not needed here
    }

    /**
     * Show the onboarding video page.
     */
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();
        
        // If user has already completed onboarding, redirect to goals dashboard
        if ($user->hasCompletedOnboarding()) {
            return redirect()->route('goals.index');
        }

        return view('onboarding.video', [
            'videoUrl' => $this->getVideoUrl(),
            'user' => $user
        ]);
    }

    /**
     * Handle onboarding completion.
     */
    public function complete(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        try {
            // Mark onboarding as complete
            $user->markOnboardingComplete();
            
            Log::info('User completed onboarding', [
                'user_id' => $user->id,
                'email' => $user->email,
                'completed_at' => now()
            ]);
            
            return redirect()->route('goals.create')
                ->with('success', 'Welcome! You\'ve completed the onboarding. Now let\'s create your first goal.');
        } catch (\Exception $e) {
            Log::error('Failed to complete onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'There was an error completing your onboarding. Please try again.');
        }
    }

    /**
     * Get the video URL for the onboarding video.
     */
    private function getVideoUrl(): string
    {
        // Return the path to the onboarding video
        return asset('videos/setting-smart-goals.mp4');
    }
}