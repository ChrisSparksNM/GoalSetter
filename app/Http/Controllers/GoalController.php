<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Services\GoalCompletionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;

class GoalController extends Controller
{
    use AuthorizesRequests;
    
    protected GoalCompletionService $goalCompletionService;
    
    public function __construct(GoalCompletionService $goalCompletionService)
    {
        $this->goalCompletionService = $goalCompletionService;
    }
    /**
     * Display a listing of the user's goals.
     */
    public function index(Request $request): View
    {
        $query = $request->user()->goals()->latest();
        
        // Apply status filter if provided
        if ($request->has('status') && in_array($request->status, ['active', 'completed', 'cancelled'])) {
            $query->where('status', $request->status);
        }
        
        $goals = $query->get();
        $statusFilter = $request->get('status', 'all');
        
        return view('goals.index', compact('goals', 'statusFilter'));
    }

    /**
     * Display the specified goal.
     */
    public function show(Goal $goal): View
    {
        // Ensure the goal belongs to the authenticated user
        $this->authorize('view', $goal);
        
        return view('goals.show', compact('goal'));
    }

    /**
     * Show the form for creating a new goal.
     */
    public function create(): View
    {
        return view('goals.create');
    }

    /**
     * Store a newly created goal in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'recurrence_type' => 'required|in:none,weekly,biweekly,monthly',
        ];

        // Conditional validation based on recurrence type
        if ($request->recurrence_type === 'none') {
            $rules['end_date'] = 'required|date|after:today';
        } else {
            $rules['start_date'] = 'required|date|after_or_equal:today';
            $rules['recurrence_count'] = 'required|integer|min:1|max:52';
        }

        $validated = $request->validate($rules, [
            'title.required' => 'The goal title is required.',
            'title.max' => 'The goal title may not be greater than 255 characters.',
            'end_date.required' => 'The end date is required for one-time goals.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after' => 'The end date must be a future date.',
            'start_date.required' => 'The start date is required for recurring goals.',
            'start_date.date' => 'The start date must be a valid date.',
            'start_date.after_or_equal' => 'The start date must be today or in the future.',
            'recurrence_count.required' => 'The number of occurrences is required for recurring goals.',
            'recurrence_count.min' => 'At least 1 occurrence is required.',
            'recurrence_count.max' => 'Maximum 52 occurrences allowed.',
            'description.max' => 'The description may not be greater than 1000 characters.',
        ]);

        try {
            if ($validated['recurrence_type'] === 'none') {
                // Create a single goal
                $goal = $request->user()->goals()->create([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'end_date' => $validated['end_date'],
                    'status' => Goal::STATUS_ACTIVE,
                    'is_recurring' => false,
                    'recurrence_type' => Goal::RECURRENCE_NONE,
                ]);

                $message = 'Goal created successfully!';
                Log::info('Single goal created', ['goal_id' => $goal->id, 'user_id' => $request->user()->id]);
            } else {
                // Create recurring goal template
                $parentGoal = $request->user()->goals()->create([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['start_date'], // First occurrence
                    'status' => Goal::STATUS_ACTIVE,
                    'is_recurring' => true,
                    'recurrence_type' => $validated['recurrence_type'],
                    'recurrence_count' => $validated['recurrence_count'],
                    'next_due_date' => $validated['start_date'],
                ]);

                // Create individual goal instances
                $createdGoals = 1;
                $currentDate = \Carbon\Carbon::parse($validated['start_date']);
                
                for ($i = 1; $i < $validated['recurrence_count']; $i++) {
                    $nextDate = match($validated['recurrence_type']) {
                        'weekly' => $currentDate->copy()->addWeeks($i),
                        'biweekly' => $currentDate->copy()->addWeeks($i * 2),
                        'monthly' => $currentDate->copy()->addMonths($i),
                        default => null,
                    };

                    if ($nextDate) {
                        $request->user()->goals()->create([
                            'title' => $validated['title'],
                            'description' => $validated['description'] ?? null,
                            'start_date' => $nextDate,
                            'end_date' => $nextDate,
                            'status' => Goal::STATUS_ACTIVE,
                            'is_recurring' => false,
                            'recurrence_type' => Goal::RECURRENCE_NONE,
                            'parent_goal_id' => $parentGoal->id,
                        ]);
                        $createdGoals++;
                    }
                }

                $message = "Recurring goal created successfully! {$createdGoals} goal instances scheduled.";
                Log::info('Recurring goal created', [
                    'parent_goal_id' => $parentGoal->id,
                    'user_id' => $request->user()->id,
                    'instances_created' => $createdGoals
                ]);
            }

            return redirect()->route('goals.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Failed to create goal', [
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create goal. Please try again.');
        }
    }
    
    /**
     * Mark a goal as complete.
     */
    public function complete(Goal $goal): RedirectResponse
    {
        // Ensure the goal belongs to the authenticated user
        $this->authorize('update', $goal);
        
        // Check if goal is already completed
        if ($goal->status === Goal::STATUS_COMPLETED) {
            return redirect()->back()
                ->with('error', 'This goal is already completed.');
        }
        
        // Check if goal is active
        if ($goal->status !== Goal::STATUS_ACTIVE) {
            return redirect()->back()
                ->with('error', 'Only active goals can be marked as complete.');
        }
        
        try {
            $this->goalCompletionService->completeGoal($goal);
            
            return redirect()->back()
                ->with('success', 'Congratulations! Goal completed successfully and notification sent.');
        } catch (\Exception $e) {
            Log::error('Failed to complete goal', [
                'goal_id' => $goal->id,
                'user_id' => $goal->user_id,
                'goal_title' => $goal->title,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to complete goal. Please try again.');
        }
    }
}