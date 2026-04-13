<?php

namespace App\Models\SurvivalArena;

use Illuminate\Database\Eloquent\Model;

class Weapon extends Model
{
    protected $table = 'sa_weapons';
    
    protected $fillable = [
        'name',
        'slug',
        'type',
        'damage',
        'fire_rate',
        'magazine_size',
        'reload_time',
        'range',
        'spread',
        'headshot_multiplier',
        'rarity',
        'model_path',
        'icon_path',
        'sound_path'
    ];

    protected $casts = [
        'reload_time' => 'float',
        'spread' => 'float',
        'headshot_multiplier' => 'float'
    ];

    // ========== Scopes ==========
    
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    public function scopeCommon($query)
    {
        return $query->where('rarity', 'common');
    }

    public function scopeRare($query)
    {
        return $query->whereIn('rarity', ['rare', 'epic', 'legendary']);
    }

    // ========== Accessors ==========
    
    public function getIconUrlAttribute(): string
    {
        return $this->icon_path 
            ? asset($this->icon_path)
            : asset('assets/images/weapons/default.png');
    }

    public function getModelUrlAttribute(): string
    {
        return $this->model_path 
            ? asset($this->model_path)
            : asset('assets/models/weapons/default.gltf');
    }

    public function getSoundUrlAttribute(): string
    {
        return $this->sound_path 
            ? asset($this->sound_path)
            : asset('assets/sounds/weapons/default.mp3');
    }

    // ========== Methods ==========
    
    public function getDPS(): float
    {
        // Damage per second
        if ($this->fire_rate <= 0) {
            return 0;
        }
        
        return $this->damage / ($this->fire_rate / 1000);
    }

    public function getEffectiveRange(): int
    {
        return (int)($this->range * 0.8);
    }

    public function calculateDamageAtDistance(float $distance): int
    {
        $falloffStart = config('games.survival-arena.combat.damage_falloff_start', 50);
        $falloffEnd = config('games.survival-arena.combat.damage_falloff_end', 100);

        if ($distance <= $falloffStart) {
            return $this->damage;
        }

        if ($distance >= $falloffEnd) {
            return (int)($this->damage * 0.5); // 50% damage at max range
        }

        // Linear falloff
        $falloffPercent = ($distance - $falloffStart) / ($falloffEnd - $falloffStart);
        $damageMultiplier = 1 - ($falloffPercent * 0.5);

        return (int)($this->damage * $damageMultiplier);
    }

    public function getRarityColorAttribute(): string
    {
        return match($this->rarity) {
            'common' => '#9CA3AF',
            'uncommon' => '#10B981',
            'rare' => '#3B82F6',
            'epic' => '#8B5CF6',
            'legendary' => '#F59E0B',
            default => '#9CA3AF'
        };
    }

    public function toGameData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'damage' => $this->damage,
            'fireRate' => $this->fire_rate,
            'magazineSize' => $this->magazine_size,
            'reloadTime' => $this->reload_time,
            'range' => $this->range,
            'spread' => $this->spread,
            'headshotMultiplier' => $this->headshot_multiplier,
            'rarity' => $this->rarity,
            'rarityColor' => $this->rarity_color,
            'modelUrl' => $this->model_url,
            'iconUrl' => $this->icon_url,
            'soundUrl' => $this->sound_url,
            'dps' => $this->getDPS(),
            'effectiveRange' => $this->getEffectiveRange()
        ];
    }
}