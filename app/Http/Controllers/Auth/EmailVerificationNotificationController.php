<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        try {
            $request->user()->sendEmailVerificationNotification();
            
            Log::info('Email verification notification resent successfully', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);

            return back()->with('success', 'A new verification link has been sent to your email address.');
        } catch (\Exception $e) {
            Log::error('Failed to resend email verification notification', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'We couldn\'t send the verification email at this time. Please try again later or contact support.');
        }
    }

    /**
     * Send a new email verification notification from login page.
     */
    public function sendFromLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.exists' => 'No account found with this email address.',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user && !$user->hasVerifiedEmail()) {
            try {
                $user->sendEmailVerificationNotification();
                
                Log::info('Email verification notification sent from login', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $request->ip()
                ]);
                
                return back()->with('success', 'A new verification link has been sent to your email address.');
            } catch (\Exception $e) {
                Log::error('Failed to send email verification from login', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'ip_address' => $request->ip()
                ]);
                
                return back()->with('error', 'We couldn\'t send the verification email at this time. Please try again later or contact support.');
            }
        }

        if ($user && $user->hasVerifiedEmail()) {
            return back()->with('info', 'Your email is already verified. You can log in now.');
        }

        return back()->withErrors(['email' => 'No account found with this email address.']);
    }
}
