<?php

namespace App\Services\SurvivalArena\Combat;

use App\Models\SurvivalArena\Weapon;

class DamageService
{
    /**
     * Calculate damage based on weapon, distance, and hit type
     */
    public function calculateDamage(
        Weapon $weapon,
        float $distance,
        bool $isHeadshot = false
    ): int {
        $baseDamage = $weapon->damage;
        
        // Apply distance falloff
        $damage = $this->applyDistanceFalloff($baseDamage, $distance, $weapon);
        
        // Apply headshot multiplier
        if ($isHeadshot) {
            $damage *= $weapon->headshot_multiplier;
        }
        
        return (int) ceil($damage);
    }
    
    /**
     * Apply distance falloff to damage
     */
    private function applyDistanceFalloff(int $baseDamage, float $distance, Weapon $weapon): float
    {
        $falloffStart = config('games.survival-arena.combat.damage_falloff_start', 50);
        $falloffEnd = config('games.survival-arena.combat.damage_falloff_end', 100);
        
        // No falloff within effective range
        if ($distance <= $falloffStart) {
            return $baseDamage;
        }
        
        // Full falloff beyond max range
        if ($distance >= $falloffEnd) {
            return $baseDamage * 0.5; // 50% damage at max range
        }
        
        // Linear falloff between start and end
        $falloffPercent = ($distance - $falloffStart) / ($falloffEnd - $falloffStart);
        $damageMultiplier = 1 - ($falloffPercent * 0.5); // Reduce by up to 50%
        
        return $baseDamage * $damageMultiplier;
    }
    
    /**
     * Calculate armor reduction
     */
    public function calculateArmorReduction(int $damage, int $armorLevel): int
    {
        $reduction = match($armorLevel) {
            1 => 0.10, // 10% reduction
            2 => 0.20, // 20% reduction
            3 => 0.30, // 30% reduction
            default => 0
        };
        
        return (int) ceil($damage * (1 - $reduction));
    }
    
    /**
     * Calculate explosion damage with falloff
     */
    public function calculateExplosionDamage(
        int $baseDamage,
        float $distance,
        float $explosionRadius
    ): int {
        if ($distance >= $explosionRadius) {
            return 0;
        }
        
        // Linear falloff from center
        $falloffPercent = $distance / $explosionRadius;
        $damage = $baseDamage * (1 - $falloffPercent);
        
        return (int) ceil($damage);
    }
    
    /**
     * Calculate fall damage
     */
    public function calculateFallDamage(float $fallDistance): int
    {
        // No damage for falls less than 5 meters
        if ($fallDistance < 5) {
            return 0;
        }
        
        // Calculate damage (5 damage per meter after 5m threshold)
        $damage = ($fallDistance - 5) * 5;
        
        // Cap at 95 (don't kill player from fall)
        return (int) min($damage, 95);
    }
}
