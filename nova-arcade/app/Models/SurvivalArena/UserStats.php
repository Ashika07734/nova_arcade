<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStats extends Model
{
    protected $table = 'sa_user_stats';
    
    protected $fillable = [
        'user_id',
        'total_matches',
        'wins',
        'top_5',
        'top_10',
        'kills',
        'deaths',
        'kd_ratio',
        'win_rate',
        'total_damage',
        'headshots',
        'longest_kill',
        'highest_kills_match',
        'total_playtime',
        'favorite_weapon_id'
    ];

    protected $casts = [
        'kd_ratio' => 'float',
        'win_rate' => 'float'
    ];

    // ========== Relationships ==========
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favoriteWeapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class, 'favorite_weapon_id');
    }

    // ========== Accessors ==========
    
    public function getFormattedKdRatioAttribute(): string
    {
        return number_format($this->kd_ratio, 2);
    }

    public function getFormattedWinRateAttribute(): string
    {
        return number_format($this->win_rate, 1) . '%';
    }

    public function getFormattedPlaytimeAttribute(): string
    {
        $hours = floor($this->total_playtime / 3600);
        $minutes = floor(($this->total_playtime % 3600) / 60);

        return sprintf('%dh %dm', $hours, $minutes);
    }

    public function getAveragePlacementAttribute(): float
    {
        // This would need to be calculated from match history
        return 0;
    }

    // ========== Methods ==========
    
    public function updateKdRatio(): void
    {
        $kd = $this->deaths > 0 ? $this->kills / $this->deaths : $this->kills;
        $this->update(['kd_ratio' => $kd]);
    }

    public function updateWinRate(): void
    {
        $winRate = $this->total_matches > 0 ? ($this->wins / $this->total_matches) * 100 : 0;
        $this->update(['win_rate' => $winRate]);
    }

    public function addPlaytime(int $seconds): void
    {
        $this->increment('total_playtime', $seconds);
    }
}

