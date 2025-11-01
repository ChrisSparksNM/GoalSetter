<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Mark the user's email address as verified.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // Get the user by ID from the route parameter
        $user = User::findOrFail($request->route('id'));
        
        // Verify the signature matches
        if (! URL::hasValidSignature($request)) {
            return redirect()->route('login')->with('error', 'Invalid verification link.');
        }
        
        // Check if email is already verified
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Your email is already verified. You can now log in.');
        }

        // Mark email as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // If user is logged in, log them out to ensure clean state
        if (Auth::check()) {
            Auth::logout();
        }
        
        return redirect()->route('login')->with('status', 'Email verified successfully! You can now log in to your account.');
    }
}
