<?php

namespace App\Services\SurvivalArena\Match;

use App\Models\SurvivalArena\ArenaMatch;
use App\Models\User;

class MatchService
{
    /**
     * Initialize match
     */
    public function initializeMatch(ArenaMatch $match): void
    {
        // Generate map data
        $match->map_data = $this->generateMapData();
        $match->save();
        
        // Create initial safe zone
        $this->createInitialSafeZone($match);
        
        // Spawn initial loot
        $this->spawnInitialLoot($match);
    }
    
    /**
     * Generate map data
     */
    private function generateMapData(): array
    {
        $obstacles = [];
        
        // Generate random obstacles
        for ($i = 0; $i < 30; $i++) {
            $obstacles[] = [
                'x' => rand(-90, 90),
                'y' => 0,
                'z' => rand(-90, 90),
                'width' => rand(2, 8),
                'height' => rand(5, 20),
                'depth' => rand(2, 8),
                'type' => 'building'
            ];
        }
        
        return [
            'obstacles' => $obstacles,
            'size' => 200,
            'terrain' => 'flat'
        ];
    }
    
    /**
     * Create initial safe zone
     */
    private function createInitialSafeZone(ArenaMatch $match): void
    {
        $config = config('games.survival-arena.safe_zone');
        
        $match->safeZones()->create([
            'phase' => 1,
            'center' => ['x' => 0, 'z' => 0],
            'radius' => $config['initial_radius'],
            'damage_per_second' => $config['phases'][1]['damage'],
            'starts_at' => now(),
            'ends_at' => now()->addSeconds($config['phases'][1]['duration'])
        ]);
    }
    
    /**
     * Spawn initial loot
     */
    private function spawnInitialLoot(ArenaMatch $match): void
    {
        // Spawn weapons
        for ($i = 0; $i < 50; $i++) {
            $match->lootSpawns()->create([
                'item_type' => 'weapon',
                'item_id' => rand(1, 5), // Random weapon
                'position' => $this->getRandomPosition(),
                'spawned_at' => now()
            ]);
        }
        
        // Spawn health packs
        for ($i = 0; $i < 30; $i++) {
            $match->lootSpawns()->create([
                'item_type' => 'health',
                'item_id' => 1,
                'position' => $this->getRandomPosition(),
                'spawned_at' => now()
            ]);
        }
        
        // Spawn shield boosts
        for ($i = 0; $i < 20; $i++) {
            $match->lootSpawns()->create([
                'item_type' => 'shield',
                'item_id' => 1,
                'position' => $this->getRandomPosition(),
                'spawned_at' => now()
            ]);
        }
    }
    
    /**
     * Get random position on map
     */
    private function getRandomPosition(): array
    {
        return [
            'x' => rand(-95, 95),
            'y' => 0.5,
            'z' => rand(-95, 95)
        ];
    }
    
    /**
     * Calculate match results
     */
    public function calculateResults(ArenaMatch $match): array
    {
        $players = $match->players()
            ->with('user')
            ->orderBy('placement')
            ->get();
        
        $results = [];
        
        foreach ($players as $player) {
            $xp = $player->calculateXP();
            
            $player->update(['xp_earned' => $xp]);
            
            $results[] = [
                'user_id' => $player->user_id,
                'username' => $player->user->username,
                'placement' => $player->placement,
                'kills' => $player->kills,
                'damage_dealt' => $player->damage_dealt,
                'survival_time' => $player->survival_time,
                'xp_earned' => $xp
            ];
        }
        
        return $results;
    }
}

