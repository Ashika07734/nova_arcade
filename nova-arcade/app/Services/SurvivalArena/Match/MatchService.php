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

        if (($match->mode ?? $match->game_mode) === 'solo') {
            $this->spawnBots($match, $match->difficulty ?? 'easy', (int) $match->bot_count);
        }
    }

    public function spawnBots(ArenaMatch $match, string $difficulty = 'easy', ?int $botCount = null): void
    {
        if (($match->mode ?? $match->game_mode) !== 'solo') {
            return;
        }

        $desired = $botCount ?? $this->botCountForDifficulty($difficulty);
        $existingBots = $match->players()->where('is_bot', true)->count();
        $toSpawn = max(0, $desired - $existingBots);

        for ($i = 1; $i <= $toSpawn; $i++) {
            $match->addBot("BOT-{$difficulty}-" . str_pad((string) ($existingBots + $i), 2, '0', STR_PAD_LEFT), $difficulty);
        }

        $match->update([
            'difficulty' => $difficulty,
            'bot_count' => $desired,
        ]);
    }
    
    /**
     * Generate map data
     */
    public function generateMapData(): array
    {
        $collisionBoxes = [
            ['name' => 'central_tower', 'x' => 0, 'y' => 0, 'z' => 0, 'width' => 18, 'height' => 70, 'depth' => 18],
            ['name' => 'office_block_a', 'x' => -42, 'y' => 0, 'z' => -28, 'width' => 16, 'height' => 28, 'depth' => 16],
            ['name' => 'office_block_b', 'x' => 36, 'y' => 0, 'z' => -18, 'width' => 18, 'height' => 34, 'depth' => 18],
            ['name' => 'parking_structure', 'x' => -20, 'y' => 0, 'z' => 34, 'width' => 24, 'height' => 12, 'depth' => 34],
            ['name' => 'warehouse', 'x' => 46, 'y' => 0, 'z' => 44, 'width' => 22, 'height' => 18, 'depth' => 26],
            ['name' => 'alley_cluster', 'x' => -56, 'y' => 0, 'z' => 18, 'width' => 14, 'height' => 16, 'depth' => 14],
            ['name' => 'rooftop_a', 'x' => 22, 'y' => 22, 'z' => 14, 'width' => 14, 'height' => 10, 'depth' => 14],
            ['name' => 'rooftop_b', 'x' => -8, 'y' => 18, 'z' => -46, 'width' => 16, 'height' => 10, 'depth' => 16],
        ];

        $spawnPoints = [
            'player' => ['x' => 10, 'y' => 1, 'z' => 20],
            'bots' => [
                ['name' => 'Building A Street', 'x' => -38, 'y' => 1, 'z' => -24],
                ['name' => 'Street B', 'x' => 28, 'y' => 1, 'z' => -14],
                ['name' => 'Alley', 'x' => -6, 'y' => 1, 'z' => -42],
                ['name' => 'Parking Area', 'x' => -52, 'y' => 1, 'z' => 16],
                ['name' => 'Tower Plaza', 'x' => -18, 'y' => 1, 'z' => 38],
                ['name' => 'Corner Street', 'x' => 4, 'y' => 1, 'z' => -6],
                ['name' => 'Warehouse Lane', 'x' => 44, 'y' => 1, 'z' => 42],
                ['name' => 'Crosswalk', 'x' => 58, 'y' => 1, 'z' => 8],
            ],
        ];

        return [
            'world' => 'city_pack_8',
            'map_name' => 'City Pack 8',
            'size' => 320,
            'terrain' => 'city',
            'map_asset' => asset('assets/models/maps/CityPack8.glb'),
            'spawn_points' => $spawnPoints,
            'collision_boxes' => $collisionBoxes,
            'obstacles' => $collisionBoxes,
            'road_lanes' => [
                ['x' => 10, 'z' => 20],
                ['x' => 18, 'z' => 4],
                ['x' => -12, 'z' => 18],
                ['x' => 26, 'z' => -6],
                ['x' => -24, 'z' => 26],
                ['x' => 34, 'z' => 12],
                ['x' => -8, 'z' => -18],
                ['x' => 12, 'z' => -28],
                ['x' => -30, 'z' => -8],
            ],
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
        $roads = $this->generateMapData()['road_lanes'];
        $point = $roads[array_rand($roads)];

        return [
            'x' => $point['x'] + rand(-4, 4),
            'y' => 0.5,
            'z' => $point['z'] + rand(-4, 4),
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
            if ($player->is_bot) {
                continue;
            }

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

    private function botCountForDifficulty(string $difficulty): int
    {
        return match ($difficulty) {
            'easy' => 3,
            'medium' => 5,
            'hard' => 8,
            default => 3,
        };
    }
}

