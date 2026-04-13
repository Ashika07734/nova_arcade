<?php

namespace App\Services\SurvivalArena\Player;

use App\Models\SurvivalArena\PlayerState;
use App\Models\User;

class PlayerService
{
    /**
     * Initialize player state
     */
    public function initializePlayer(int $matchId, User $user, array $spawnPosition): PlayerState
    {
        return PlayerState::create([
            'match_id' => $matchId,
            'user_id' => $user->id,
            'position' => $spawnPosition,
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'velocity' => ['x' => 0, 'y' => 0, 'z' => 0],
            'health' => 100,
            'shield' => 0,
            'stamina' => 100,
            'inventory' => [],
            'last_updated' => now()
        ]);
    }
    
    /**
     * Validate player action
     */
    public function validateAction(PlayerState $player, string $action): bool
    {
        // Check if player is alive
        if ($player->health <= 0) {
            return false;
        }
        
        // Check specific action requirements
        return match($action) {
            'shoot' => !$player->is_reloading && $player->ammo_current > 0,
            'reload' => !$player->is_reloading && $player->ammo_reserve > 0,
            'jump' => $player->stamina > 10,
            'sprint' => $player->stamina > 0,
            default => true
        };
    }
    
    /**
     * Update stamina
     */
    public function updateStamina(PlayerState $player, bool $isSprinting): void
    {
        if ($isSprinting && $player->stamina > 0) {
            $player->stamina = max(0, $player->stamina - 1);
        } elseif (!$isSprinting && $player->stamina < 100) {
            $player->stamina = min(100, $player->stamina + 2);
        }
        
        $player->save();
    }
}

