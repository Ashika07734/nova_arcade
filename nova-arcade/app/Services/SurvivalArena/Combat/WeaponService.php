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

        $this->resolveMatchPlayer($shooter)?->incrementShotsFired();
        
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
        $shooterKey = $shooter->user_id ?: ('bot:' . ($shooter->bot_name ?: $shooter->id));
        $lastShot = Cache::get("player:{$shooterKey}:last_shot");
        if ($lastShot && now()->diffInMilliseconds($lastShot) < $weapon->fire_rate) {
            return false;
        }
        
        Cache::put(
            "player:{$shooterKey}:last_shot",
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
        $shooterRecord = $this->resolveMatchPlayer($shooter);

        if ($shooterRecord) {
            $shooterRecord->addDamage($damage);
            $shooterRecord->incrementShotsHit();
        }
        
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

        $killerName = $shooter->is_bot
            ? ($shooter->bot_name ?: 'BOT')
            : ($shooter->user?->username ?? 'Player');
        $victimName = $victim->is_bot
            ? ($victim->bot_name ?: 'BOT')
            : ($victim->user?->username ?? 'Player');
        
        if ($isKill && $isHeadshot) {
            $shooterRecord?->incrementHeadshots();

            $this->pushKillFeedEntry($shooter->match->id, [
                'killer_id' => $shooter->user_id,
                'killer_name' => $killerName,
                'killer_is_bot' => (bool) $shooter->is_bot,
                'victim_id' => $victim->user_id,
                'victim_name' => $victimName,
                'victim_is_bot' => (bool) $victim->is_bot,
                'weapon_name' => $weapon->name,
                'headshot' => $isHeadshot,
                'created_at' => now()->toIso8601String(),
            ]);
        } elseif ($isKill) {
            $this->pushKillFeedEntry($shooter->match->id, [
                'killer_id' => $shooter->user_id,
                'killer_name' => $killerName,
                'killer_is_bot' => (bool) $shooter->is_bot,
                'victim_id' => $victim->user_id,
                'victim_name' => $victimName,
                'victim_is_bot' => (bool) $victim->is_bot,
                'weapon_name' => $weapon->name,
                'headshot' => false,
                'created_at' => now()->toIso8601String(),
            ]);
        }
        
        return [
            'success' => true,
            'hit' => true,
            'killer_name' => $killerName,
            'victim_id' => $victim->user_id,
            'victim_is_bot' => (bool) $victim->is_bot,
            'victim_bot_name' => $victim->bot_name,
            'victim_name' => $victimName,
            'damage' => $damage,
            'headshot' => $isHeadshot,
            'kill' => $isKill,
            'distance' => $hit['distance']
        ];
    }

    private function resolveMatchPlayer(PlayerState $state): ?\App\Models\SurvivalArena\MatchPlayer
    {
        if ($state->is_bot) {
            return $state->match->players()
                ->where('is_bot', true)
                ->where('bot_name', $state->bot_name)
                ->first();
        }

        return $state->match->players()
            ->where('user_id', $state->user_id)
            ->first();
    }

    private function pushKillFeedEntry(int $matchId, array $entry): void
    {
        $key = "match:{$matchId}:kill_feed";
        $feed = Cache::get($key, []);

        array_unshift($feed, $entry);
        $feed = array_slice($feed, 0, 20);

        Cache::put($key, $feed, now()->addMinutes(30));
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

