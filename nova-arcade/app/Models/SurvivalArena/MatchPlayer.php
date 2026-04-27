<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchPlayer extends Model
{
    protected $table = 'sa_match_players';
    
    protected $fillable = [
        'match_id',
        'user_id',
        'is_bot',
        'bot_name',
        'bot_difficulty',
        'team_id',
        'kills',
        'deaths',
        'damage_dealt',
        'damage_taken',
        'headshots',
        'placement',
        'xp_earned',
        'score',
        'shots_fired',
        'shots_hit',
        'survival_time',
        'final_position',
        'is_alive',
        'joined_at',
        'died_at'
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'final_position' => 'array',
        'is_alive' => 'boolean',
        'joined_at' => 'datetime',
        'died_at' => 'datetime'
    ];

    // ========== Relationships ==========
    
    public function match(): BelongsTo
    {
        return $this->belongsTo(ArenaMatch::class, 'match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeBots($query)
    {
        return $query->where('is_bot', true);
    }

    public function scopeHumans($query)
    {
        return $query->where('is_bot', false);
    }

    // ========== Methods ==========
    
    public function incrementKills(): void
    {
        $this->increment('kills');
    }

    public function incrementHeadshots(): void
    {
        $this->increment('headshots');
    }

    public function incrementShotsFired(): void
    {
        $this->increment('shots_fired');
    }

    public function incrementShotsHit(): void
    {
        $this->increment('shots_hit');
    }

    public function addDamage(int $amount): void
    {
        $this->increment('damage_dealt', $amount);
    }

    public function takeDamage(int $amount): void
    {
        $this->increment('damage_taken', $amount);
    }

    public function recordDeath(array $position): void
    {
        $this->update([
            'is_alive' => false,
            'died_at' => now(),
            'final_position' => $position,
            'deaths' => $this->deaths + 1,
            'survival_time' => $this->calculateSurvivalTime()
        ]);
    }

    public function calculateSurvivalTime(): int
    {
        if (!$this->died_at) {
            return now()->diffInSeconds($this->joined_at);
        }

        return $this->died_at->diffInSeconds($this->joined_at);
    }

    public function calculateXP(): int
    {
        $xp = 0;

        // Base XP for participation
        $xp += 50;

        // XP per kill
        $xp += $this->kills * 100;

        // XP for headshots
        $xp += $this->headshots * 50;

        // XP based on placement
        if ($this->placement === 1) {
            $xp += 500;
        } elseif ($this->placement <= 3) {
            $xp += 300;
        } elseif ($this->placement <= 5) {
            $xp += 200;
        } elseif ($this->placement <= 10) {
            $xp += 100;
        }

        // XP based on damage dealt
        $xp += floor($this->damage_dealt / 10);

        // Survival time bonus
        $xp += floor($this->survival_time / 60) * 10; // 10 XP per minute

        return $xp;
    }

    public function getFormattedSurvivalTimeAttribute(): string
    {
        $minutes = floor($this->survival_time / 60);
        $seconds = $this->survival_time % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getAccuracyAttribute(): float
    {
        if ($this->shots_fired <= 0) {
            return 0.0;
        }

        return ($this->shots_hit / $this->shots_fired) * 100;
    }
}