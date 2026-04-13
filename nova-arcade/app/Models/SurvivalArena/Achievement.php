<?php

namespace App\Models\SurvivalArena;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    protected $table = 'sa_achievements';
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'type',
        'requirement',
        'reward_xp',
        'rarity'
    ];

    protected $casts = [
        'requirement' => 'array'
    ];

    // ========== Relationships ==========
    
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    // ========== Scopes ==========
    
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    // ========== Accessors ==========
    
    public function getIconUrlAttribute(): string
    {
        return $this->icon 
            ? asset('assets/images/achievements/' . $this->icon)
            : asset('assets/images/achievements/default.png');
    }

    public function getRarityColorAttribute(): string
    {
        return match($this->rarity) {
            'common' => '#9CA3AF',
            'rare' => '#3B82F6',
            'epic' => '#8B5CF6',
            'legendary' => '#F59E0B',
            default => '#9CA3AF'
        };
    }
}

