<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description', 
        'end_date',
        'status',
        'completed_at',
        'is_recurring',
        'recurrence_type',
        'start_date',
        'recurrence_count',
        'next_due_date',
        'parent_goal_id'
    ];

    protected $casts = [
        'end_date' => 'date',
        'completed_at' => 'datetime',
        'start_date' => 'date',
        'next_due_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const RECURRENCE_NONE = 'none';
    const RECURRENCE_WEEKLY = 'weekly';
    const RECURRENCE_BIWEEKLY = 'biweekly';
    const RECURRENCE_MONTHLY = 'monthly';

    /**
     * Get the user that owns the goal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifications for the goal.
     */
    public function goalNotifications(): HasMany
    {
        return $this->hasMany(GoalNotification::class);
    }

    /**
     * Mark the goal as complete.
     */
    public function markComplete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ]);
    }

    /**
     * Check if the goal is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               $this->end_date->isPast();
    }

    /**
     * Get the number of days remaining until the end date.
     */
    public function getDaysRemaining(): int
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return 0;
        }

        return max(0, now()->startOfDay()->diffInDays($this->end_date->startOfDay(), false));
    }

    /**
     * Scope to get active goals.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get completed goals.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Get the parent goal (for recurring goal instances).
     */
    public function parentGoal(): BelongsTo
    {
        return $this->belongsTo(Goal::class, 'parent_goal_id');
    }

    /**
     * Get child goals (recurring instances).
     */
    public function childGoals(): HasMany
    {
        return $this->hasMany(Goal::class, 'parent_goal_id');
    }

    /**
     * Create the next recurring instance of this goal.
     */
    public function createNextRecurrence(): ?Goal
    {
        if (!$this->is_recurring || $this->recurrence_type === self::RECURRENCE_NONE) {
            return null;
        }

        $nextDueDate = $this->calculateNextDueDate();
        
        if (!$nextDueDate) {
            return null;
        }

        return Goal::create([
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $nextDueDate,
            'end_date' => $nextDueDate,
            'status' => self::STATUS_ACTIVE,
            'is_recurring' => false, // Individual instances are not recurring
            'recurrence_type' => self::RECURRENCE_NONE,
            'parent_goal_id' => $this->id,
        ]);
    }

    /**
     * Calculate the next due date based on recurrence type.
     */
    private function calculateNextDueDate(): ?Carbon
    {
        $baseDate = $this->next_due_date ?? $this->end_date;
        
        return match($this->recurrence_type) {
            self::RECURRENCE_WEEKLY => $baseDate->copy()->addWeek(),
            self::RECURRENCE_BIWEEKLY => $baseDate->copy()->addWeeks(2),
            self::RECURRENCE_MONTHLY => $baseDate->copy()->addMonth(),
            default => null,
        };
    }

    /**
     * Update the next due date for recurring goals.
     */
    public function updateNextDueDate(): void
    {
        if ($this->is_recurring && $this->recurrence_type !== self::RECURRENCE_NONE) {
            $this->update([
                'next_due_date' => $this->calculateNextDueDate()
            ]);
        }
    }

    /**
     * Get recurrence type display name.
     */
    public function getRecurrenceDisplayAttribute(): string
    {
        return match($this->recurrence_type) {
            self::RECURRENCE_WEEKLY => 'Weekly',
            self::RECURRENCE_BIWEEKLY => 'Every 2 weeks',
            self::RECURRENCE_MONTHLY => 'Monthly',
            default => 'One-time',
        };
    }
}
