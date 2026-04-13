<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerKill extends Model
{
    protected $table = 'sa_player_kills';
    
    protected $fillable = [
        'match_id',
        'killer_id',
        'victim_id',
        'weapon_id',
        'distance',
        'headshot',
        'kill_position'
    ];

    protected $casts = [
        'distance' => 'float',
        'headshot' => 'boolean',
        'kill_position' => 'array'
    ];

    // ========== Relationships ==========
    
    public function match(): BelongsTo
    {
        return $this->belongsTo(ArenaMatch::class, 'match_id');
    }

    public function killer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'killer_id');
    }

    public function victim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'victim_id');
    }

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class);
    }

    // ========== Accessors ==========
    
    public function getFormattedDistanceAttribute(): string
    {
        return number_format($this->distance, 1) . 'm';
    }

    // ========== Methods ==========
    
    public function isLongRangeKill(): bool
    {
        return $this->distance > 100;
    }

    public function toGameData(): array
    {
        return [
            'killerId' => $this->killer_id,
            'killerName' => $this->killer->name,
            'victimId' => $this->victim_id,
            'victimName' => $this->victim->name,
            'weaponName' => $this->weapon?->name ?? 'Unknown',
            'distance' => $this->distance,
            'headshot' => $this->headshot,
            'timestamp' => $this->created_at->timestamp
        ];
    }
}

