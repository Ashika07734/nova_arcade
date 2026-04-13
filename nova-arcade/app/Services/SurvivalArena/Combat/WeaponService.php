<?php

namespace App\Services\SurvivalArena\Combat;

use App\Models\SurvivalArena\Weapon;
use App\Models\SurvivalArena\PlayerState;
use App\Models\SurvivalArena\PlayerKill;
use App\Events\SurvivalArena\Player\PlayerShot;
use App\Events\SurvivalArena\Player\PlayerDamaged;
use Illuminate\Support\Facades\Cache;

class WeaponService
{
    public function __construct(
        private HitDetectionService $hitDetectionService,
        private DamageService $damageService
    ) {}
    
    /**
     * Process weapon shot
     */
    public function shoot(PlayerState $shooter, array $direction, Weapon $weapon): array
    {
        // Check if can shoot
        if (!$this->canShoot($shooter, $weapon)) {
            return [
                'success' => false,
                'reason' => 'Cannot shoot'
            ];
        }
        
        // Consume ammo
        $shooter->decrement('ammo_current');
        $shooter->is_shooting = true;
        $shooter->save();
        
        // Apply weapon spread
        $direction = $this->applySpread($direction, $weapon->spread);
        
        // Broadcast shot event
        broadcast(new PlayerShot(
            $shooter->match,
            $shooter->user_id,
            $direction,
            $weapon->id
        ))->toOthers();
        
        // Perform hit detection
        $hit = $this->hitDetectionService->raycast(
            $shooter,
            $direction,
            $weapon->range
        );
        
        if ($hit) {
            return $this->processHit($shooter, $hit, $weapon, $direction);
        }
        
        return [
            'success' => true,
            'hit' => false
        ];
    }
    
    /**
     * Check if player can shoot
     */
    private function canShoot(PlayerState $shooter, Weapon $weapon): bool
    {
        // Check ammo
        if ($shooter->ammo_current <= 0) {
            return false;
        }
        
        // Check if reloading
        if ($shooter->is_reloading) {
            return false;
        }
        
        // Check fire rate cooldown
        $lastShot = Cache::get("player:{$shooter->user_id}:last_shot");
        if ($lastShot && now()->diffInMilliseconds($lastShot) < $weapon->fire_rate) {
            return false;
        }
        
        Cache::put(
            "player:{$shooter->user_id}:last_shot",
            now(),
            now()->addSeconds(1)
        );
        
        return true;
    }
    
    /**
     * Apply weapon spread to direction
     */
    private function applySpread(array $direction, float $spread): array
    {
        return [
            'x' => $direction['x'] + (rand(-100, 100) / 100) * $spread,
            'y' => $direction['y'] + (rand(-100, 100) / 100) * $spread,
            'z' => $direction['z'] + (rand(-100, 100) / 100) * $spread
        ];
    }
    
    /**
     * Process hit on target
     */
    private function processHit(
        PlayerState $shooter,
        array $hit,
        Weapon $weapon,
        array $direction
    ): array {
        $victim = $hit['player'];
        $isHeadshot = $hit['headshot'] ?? false;
        
        // Calculate damage
        $damage = $this->damageService->calculateDamage(
            $weapon,
            $hit['distance'],
            $isHeadshot
        );
        
        // Apply damage to victim
        $victim->takeDamage($damage, $shooter->user_id);
        
        // Update shooter's damage dealt
        $shooter->match->players()
            ->where('user_id', $shooter->user_id)
            ->first()
            ->addDamage($damage);
        
        // Broadcast damage event
        broadcast(new PlayerDamaged(
            $victim->match,
            $victim->user_id,
            $damage,
            $victim->health,
            $victim->shield,
            $shooter->user_id
        ))->toOthers();
        
        // Check if kill
        $isKill = $victim->health <= 0;
        
        if ($isKill && $isHeadshot) {
            $shooter->match->players()
                ->where('user_id', $shooter->user_id)
                ->first()
                ->incrementHeadshots();
        }
        
        return [
            'success' => true,
            'hit' => true,
            'victim_id' => $victim->user_id,
            'damage' => $damage,
            'headshot' => $isHeadshot,
            'kill' => $isKill,
            'distance' => $hit['distance']
        ];
    }
    
    /**
     * Get weapon DPS (Damage Per Second)
     */
    public function getWeaponDPS(Weapon $weapon): float
    {
        if ($weapon->fire_rate <= 0) {
            return 0;
        }
        
        $shotsPerSecond = 1000 / $weapon->fire_rate;
        return $weapon->damage * $shotsPerSecond;
    }
    
    /**
     * Get time to kill (TTK) in seconds
     */
    public function getTimeToKill(Weapon $weapon, int $targetHealth = 100): float
    {
        $dps = $this->getWeaponDPS($weapon);
        
        if ($dps <= 0) {
            return 0;
        }
        
        return $targetHealth / $dps;
    }
}

