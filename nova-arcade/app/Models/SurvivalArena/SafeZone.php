<?php

namespace App\Models\SurvivalArena;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafeZone extends Model
{
    protected $table = 'sa_safe_zones';
    
    protected $fillable = [
        'match_id',
        'phase',
        'center',
        'radius',
        'damage_per_second',
        'starts_at',
        'ends_at'
    ];

    protected $casts = [
        'center' => 'array',
        'radius' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime'
    ];

    // ========== Relationships ==========
    
    public function match(): BelongsTo
    {
        return $this->belongsTo(ArenaMatch::class, 'match_id');
    }

    // ========== Scopes ==========
    
    public function scopeCurrent($query)
    {
        return $query->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', now());
    }

    // ========== Methods ==========
    
    public function isActive(): bool
    {
        return now()->between($this->starts_at, $this->ends_at);
    }

    public function getTimeRemaining(): int
    {
        if (!$this->isActive()) {
            return 0;
        }

        return $this->ends_at->diffInSeconds(now());
    }

    public function isPlayerInside(array $position): bool
    {
        $distance = sqrt(
            pow($position['x'] - $this->center['x'], 2) +
            pow($position['z'] - $this->center['z'], 2)
        );

        return $distance <= $this->radius;
    }

    public function toGameData(): array
    {
        return [
            'phase' => $this->phase,
            'center' => $this->center,
            'radius' => $this->radius,
            'damagePerSecond' => $this->damage_per_second,
            'timeRemaining' => $this->getTimeRemaining(),
            'isActive' => $this->isActive()
        ];
    }
}

