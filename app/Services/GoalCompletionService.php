<?php

namespace App\Services;

use App\Mail\GoalCompletionMail;
use App\Models\Goal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GoalCompletionService
{
    /**
     * Complete a goal and handle all related business logic.
     */
    public function completeGoal(Goal $goal): void
    {
        // Update goal status and completion date
        $this->updateGoalStatus($goal);
        
        // Send completion notification
        $this->sendCompletionNotification($goal);
        
        Log::info('Goal completed successfully', [
            'goal_id' => $goal->id,
            'user_id' => $goal->user_id,
            'title' => $goal->title,
            'completed_at' => $goal->completed_at
        ]);
    }
    
    /**
     * Update the goal status to completed.
     */
    private function updateGoalStatus(Goal $goal): void
    {
        $goal->markComplete();
    }
    
    /**
     * Send completion notification email.
     */
    private function sendCompletionNotification(Goal $goal): void
    {
        try {
            $recipientEmail = config('mail.goal_notification_recipient', env('GOAL_NOTIFICATION_RECIPIENT'));
            
            if (empty($recipientEmail)) {
                Log::warning('Goal completion notification recipient not configured', [
                    'goal_id' => $goal->id
                ]);
                return;
            }

            Mail::to($recipientEmail)->send(new GoalCompletionMail($goal));
            
            Log::info('Goal completion notification sent successfully', [
                'goal_id' => $goal->id,
                'user_name' => $goal->user->name,
                'goal_title' => $goal->title,
                'recipient' => $recipientEmail,
                'completed_at' => $goal->completed_at
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send goal completion notification', [
                'goal_id' => $goal->id,
                'user_name' => $goal->user->name,
                'goal_title' => $goal->title,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw the exception - goal completion should still succeed
        }
    }
}