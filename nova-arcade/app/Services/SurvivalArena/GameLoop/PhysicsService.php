<?php

namespace App\Services\SurvivalArena\GameLoop;

use App\Models\SurvivalArena\PlayerState;

class PhysicsService
{
    private float $gravity;
    private float $groundLevel = 0;
    private float $tickRate = 60;
    private float $deltaTime;
    
    public function __construct()
    {
        $this->gravity = config('games.survival-arena.physics.gravity', -9.81);
        $this->deltaTime = 1 / $this->tickRate; // ~0.0167 seconds
    }
    
    /**
     * Apply gravity to player
     */
    public function applyGravity(PlayerState $player): void
    {
        $position = $player->position;
        $velocity = $player->velocity ?? ['x' => 0, 'y' => 0, 'z' => 0];
        
        // Apply gravity
        $velocity['y'] += $this->gravity * $this->deltaTime;
        
        // Clamp fall speed
        $maxFallSpeed = config('games.survival-arena.physics.max_fall_speed', -50.0);
        $velocity['y'] = max($velocity['y'], $maxFallSpeed);
        
        // Update position based on velocity
        $position['y'] += $velocity['y'] * $this->deltaTime;
        
        // Ground collision
        if ($position['y'] <= $this->groundLevel + 1) {
            $position['y'] = $this->groundLevel + 1;
            $velocity['y'] = 0;
        }
        
        $player->position = $position;
        $player->velocity = $velocity;
    }
    
    /**
     * Update velocity based on player input
     */
    public function updateVelocity(PlayerState $player): void
    {
        $velocity = $player->velocity ?? ['x' => 0, 'y' => 0, 'z' => 0];
        
        // Apply friction/air resistance
        $friction = 0.9;
        $velocity['x'] *= $friction;
        $velocity['z'] *= $friction;
        
        // Stop if velocity is very small
        if (abs($velocity['x']) < 0.01) $velocity['x'] = 0;
        if (abs($velocity['z']) < 0.01) $velocity['z'] = 0;
        
        $player->velocity = $velocity;
    }
    
    /**
     * Apply jump force
     */
    public function jump(PlayerState $player): bool
    {
        $velocity = $player->velocity ?? ['x' => 0, 'y' => 0, 'z' => 0];
        
        // Only jump if on ground
        if ($player->position['y'] <= $this->groundLevel + 1.1) {
            $velocity['y'] = config('games.survival-arena.physics.jump_force', 5.0);
            $player->velocity = $velocity;
            return true;
        }
        
        return false;
    }
    
    /**
     * Calculate distance between two positions
     */
    public function calculateDistance(array $pos1, array $pos2): float
    {
        return sqrt(
            pow($pos2['x'] - $pos1['x'], 2) +
            pow($pos2['y'] - $pos1['y'], 2) +
            pow($pos2['z'] - $pos1['z'], 2)
        );
    }
    
    /**
     * Check if position is valid (not out of bounds)
     */
    public function isPositionValid(array $position): bool
    {
        $mapSize = 200; // 200x200 map
        
        return abs($position['x']) <= $mapSize / 2 
            && abs($position['z']) <= $mapSize / 2
            && $position['y'] >= 0;
    }
}

