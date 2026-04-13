<?php

namespace App\Services\SurvivalArena\GameLoop;

use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\PlayerState;
use Illuminate\Support\Collection;

class CollisionService
{
    private float $playerRadius = 0.5;
    
    /**
     * Check all collisions
     */
    public function checkCollisions(ArenaMatch $match, Collection $players): void
    {
        // Check player-player collisions
        $this->checkPlayerCollisions($players);
        
        // Check player-obstacle collisions
        foreach ($players as $player) {
            $this->checkObstacleCollisions($player, $match);
        }
    }
    
    /**
     * Check player-player collisions
     */
    private function checkPlayerCollisions(Collection $players): void
    {
        $playersArray = $players->all();
        $count = count($playersArray);
        
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                if ($this->arePlayersColliding($playersArray[$i], $playersArray[$j])) {
                    $this->resolvePlayerCollision($playersArray[$i], $playersArray[$j]);
                }
            }
        }
    }
    
    /**
     * Check if two players are colliding
     */
    private function arePlayersColliding(PlayerState $a, PlayerState $b): bool
    {
        $distance = sqrt(
            pow($b->position['x'] - $a->position['x'], 2) +
            pow($b->position['z'] - $a->position['z'], 2)
        );
        
        return $distance < ($this->playerRadius * 2);
    }
    
    /**
     * Resolve player collision (push apart)
     */
    private function resolvePlayerCollision(PlayerState $a, PlayerState $b): void
    {
        $direction = [
            'x' => $a->position['x'] - $b->position['x'],
            'z' => $a->position['z'] - $b->position['z']
        ];
        
        $length = sqrt($direction['x'] ** 2 + $direction['z'] ** 2);
        
        if ($length > 0) {
            // Normalize direction
            $direction['x'] /= $length;
            $direction['z'] /= $length;
            
            // Push both players apart
            $pushForce = 0.5;
            
            $a->position['x'] += $direction['x'] * $pushForce;
            $a->position['z'] += $direction['z'] * $pushForce;
            
            $b->position['x'] -= $direction['x'] * $pushForce;
            $b->position['z'] -= $direction['z'] * $pushForce;
        }
    }
    
    /**
     * Check player-obstacle collisions
     */
    private function checkObstacleCollisions(PlayerState $player, ArenaMatch $match): void
    {
        $obstacles = $match->map_data['obstacles'] ?? [];
        
        foreach ($obstacles as $obstacle) {
            if ($this->isPlayerInObstacle($player->position, $obstacle)) {
                $this->resolveObstacleCollision($player, $obstacle);
            }
        }
    }
    
    /**
     * Check if player is inside obstacle (AABB collision)
     */
    private function isPlayerInObstacle(array $position, array $obstacle): bool
    {
        return (
            $position['x'] > $obstacle['x'] - $obstacle['width'] / 2 - $this->playerRadius &&
            $position['x'] < $obstacle['x'] + $obstacle['width'] / 2 + $this->playerRadius &&
            $position['z'] > $obstacle['z'] - $obstacle['depth'] / 2 - $this->playerRadius &&
            $position['z'] < $obstacle['z'] + $obstacle['depth'] / 2 + $this->playerRadius
        );
    }
    
    /**
     * Resolve obstacle collision
     */
    private function resolveObstacleCollision(PlayerState $player, array $obstacle): void
    {
        $closestX = max(
            $obstacle['x'] - $obstacle['width'] / 2,
            min($player->position['x'], $obstacle['x'] + $obstacle['width'] / 2)
        );
        
        $closestZ = max(
            $obstacle['z'] - $obstacle['depth'] / 2,
            min($player->position['z'], $obstacle['z'] + $obstacle['depth'] / 2)
        );
        
        $distX = $player->position['x'] - $closestX;
        $distZ = $player->position['z'] - $closestZ;
        
        // Push out in the direction of smallest penetration
        if (abs($distX) > abs($distZ)) {
            $player->position['x'] = $closestX + ($distX > 0 ? $this->playerRadius : -$this->playerRadius);
        } else {
            $player->position['z'] = $closestZ + ($distZ > 0 ? $this->playerRadius : -$this->playerRadius);
        }
        
        // Stop velocity in collision direction
        $velocity = $player->velocity ?? ['x' => 0, 'y' => 0, 'z' => 0];
        if (abs($distX) > abs($distZ)) {
            $velocity['x'] = 0;
        } else {
            $velocity['z'] = 0;
        }
        $player->velocity = $velocity;
    }
}

