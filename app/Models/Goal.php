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
        'completed_at'
    ];

    protected $casts = [
        'end_date' => 'date',
        'completed_at' => 'datetime',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

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
}
