<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendEmailVerificationNotificationWithErrorHandling
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        try {
            if ($event->user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $event->user->hasVerifiedEmail()) {
                $event->user->sendEmailVerificationNotification();
                
                Log::info('Email verification notification sent successfully', [
                    'user_id' => $event->user->id,
                    'email' => $event->user->email
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send email verification notification', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't re-throw the exception - we want registration to succeed even if email fails
        }
    }
}