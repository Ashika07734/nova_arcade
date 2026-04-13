<?php

namespace App\Services\SurvivalArena\Player;

use App\Models\SurvivalArena\PlayerState;

class MovementService
{
    private float $playerSpeed;
    private float $sprintMultiplier;
    
    public function __construct()
    {
        $this->playerSpeed = config('games.survival-arena.physics.player_speed', 5.0);
        $this->sprintMultiplier = config('games.survival-arena.physics.sprint_multiplier', 1.5);
    }
    
    /**
     * Update player position
     */
    public function updatePosition(PlayerState $player): void
    {
        $velocity = $player->velocity ?? ['x' => 0, 'y' => 0, 'z' => 0];
        $position = $player->position;
        
        $deltaTime = 1 / 60; // 60fps
        
        // Update position based on velocity
        $position['x'] += $velocity['x'] * $deltaTime;
        $position['y'] += $velocity['y'] * $deltaTime;
        $position['z'] += $velocity['z'] * $deltaTime;
        
        $player->position = $position;
    }
    
    /**
     * Calculate movement velocity from input
     */
    public function calculateVelocity(array $input, PlayerState $player): array
    {
        $speed = $this->playerSpeed;
        
        // Apply sprint multiplier
        if ($input['sprinting'] ?? false) {
            $speed *= $this->sprintMultiplier;
        }
        
        // Apply crouch penalty
        if ($input['crouching'] ?? false) {
            $speed *= 0.5;
        }
        
        $velocity = ['x' => 0, 'y' => 0, 'z' => 0];
        
        // Calculate direction from input
        if (isset($input['direction'])) {
            $velocity['x'] = $input['direction']['x'] * $speed;
            $velocity['z'] = $input['direction']['z'] * $speed;
        }
        
        return $velocity;
    }
}

