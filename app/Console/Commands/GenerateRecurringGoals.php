<?php

namespace App\Console\Commands;

use App\Models\Goal;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateRecurringGoals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goals:generate-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate upcoming instances of recurring goals';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating recurring goal instances...');
        
        // Find recurring goals that need new instances
        $recurringGoals = Goal::where('is_recurring', true)
            ->where('recurrence_type', '!=', Goal::RECURRENCE_NONE)
            ->whereNotNull('next_due_date')
            ->where('next_due_date', '<=', now()->addDays(7)) // Generate for next week
            ->get();
            
        $generated = 0;
        
        foreach ($recurringGoals as $goal) {
            if ($this->shouldGenerateNext($goal)) {
                $nextGoal = $goal->createNextRecurrence();
                if ($nextGoal) {
                    $goal->updateNextDueDate();
                    $generated++;
                    $this->line("Generated: {$goal->title} for {$nextGoal->end_date->format('Y-m-d')}");
                }
            }
        }
        
        $this->info("Generated {$generated} recurring goal instances.");
        
        return 0;
    }
    
    /**
     * Check if we should generate the next instance of a recurring goal.
     */
    private function shouldGenerateNext(Goal $goal): bool
    {
        // Check if there's already a goal instance for the next due date
        $existingGoal = Goal::where('parent_goal_id', $goal->id)
            ->where('end_date', $goal->next_due_date)
            ->exists();
            
        return !$existingGoal;
    }
}
