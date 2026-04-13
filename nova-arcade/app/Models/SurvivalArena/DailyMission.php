<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMission extends Model
{
    protected $table = 'sa_daily_missions';
    
    protected $fillable = [
        'user_id',
        'date',
        'mission_type',
        'description',
        'target',
        'progress',
        'completed',
        'reward_xp',
        'completed_at'
    ];

    protected $casts = [
        'date' => 'date',
        'completed' => 'boolean',
        'completed_at' => 'datetime'
    ];

    // ========== Relationships ==========
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Scopes ==========
    
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopeIncomplete($query)
    {
        return $query->where('completed', false);
    }

    // ========== Accessors ==========
    
    public function getProgressPercentageAttribute(): int
    {
        if ($this->target <= 0) {
            return 0;
        }

        return (int)(($this->progress / $this->target) * 100);
    }

    // ========== Methods ==========
    
    public function addProgress(int $amount = 1): void
    {
        $this->progress = min($this->target, $this->progress + $amount);

        if ($this->progress >= $this->target && !$this->completed) {
            $this->complete();
        } else {
            $this->save();
        }
    }

    protected function complete(): void
    {
        $this->update([
            'progress' => $this->target,
            'completed' => true,
            'completed_at' => now()
        ]);

        // Award XP to user
        // This would be handled by an event listener
    }

    public function claim(): bool
    {
        if (!$this->completed) {
            return false;
        }

        // Award rewards
        // This would update user XP/stats

        return true;
    }
}

