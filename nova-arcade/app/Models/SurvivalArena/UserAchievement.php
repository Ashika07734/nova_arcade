<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    protected $table = 'sa_user_achievements';
    
    protected $fillable = [
        'user_id',
        'achievement_id',
        'progress',
        'unlocked',
        'unlocked_at'
    ];

    protected $casts = [
        'unlocked' => 'boolean',
        'unlocked_at' => 'datetime'
    ];

    // ========== Relationships ==========
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    // ========== Scopes ==========
    
    public function scopeUnlocked($query)
    {
        return $query->where('unlocked', true);
    }

    public function scopeInProgress($query)
    {
        return $query->where('unlocked', false)
            ->where('progress', '>', 0);
    }

    // ========== Methods ==========
    
    public function addProgress(int $amount = 1): void
    {
        $requirement = $this->achievement->requirement;
        $target = $requirement['target'] ?? 1;

        $this->progress = min($target, $this->progress + $amount);

        if ($this->progress >= $target && !$this->unlocked) {
            $this->unlock();
        } else {
            $this->save();
        }
    }

    protected function unlock(): void
    {
        $this->update([
            'unlocked' => true,
            'unlocked_at' => now()
        ]);

        // Broadcast achievement unlocked event
        // Award XP
    }

    public function getProgressPercentageAttribute(): int
    {
        $requirement = $this->achievement->requirement;
        $target = $requirement['target'] ?? 1;

        if ($target <= 0) {
            return 0;
        }

        return (int)(($this->progress / $target) * 100);
    }
}