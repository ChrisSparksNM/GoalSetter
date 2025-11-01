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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'end_date' => 'required|date|after:today',
        ], [
            'title.required' => 'The goal title is required.',
            'title.max' => 'The goal title may not be greater than 255 characters.',
            'end_date.required' => 'The end date is required.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after' => 'The end date must be a future date.',
            'description.max' => 'The description may not be greater than 1000 characters.',
        ]);

        try {
            $goal = $request->user()->goals()->create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'end_date' => $validated['end_date'],
                'status' => Goal::STATUS_ACTIVE,
            ]);

            Log::info('Goal created successfully', [
                'goal_id' => $goal->id,
                'user_id' => $request->user()->id,
                'title' => $goal->title,
                'end_date' => $goal->end_date
            ]);

            return redirect()->route('goals.index')
                ->with('success', 'Goal created successfully!');
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