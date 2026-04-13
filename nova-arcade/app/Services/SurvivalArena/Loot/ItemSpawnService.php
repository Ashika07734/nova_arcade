<?php

namespace App\Services\SurvivalArena\Loot;

use App\Models\SurvivalArena\ArenaMatch;

class ItemSpawnService
{
    /**
     * Generate spawn points for loot
     */
    public function generateSpawnPoints(ArenaMatch $match, int $count = 100): array
    {
        $spawnPoints = [];
        $mapSize = 200;
        
        // Get obstacles to avoid spawning inside them
        $obstacles = $match->map_data['obstacles'] ?? [];
        
        $attempts = 0;
        $maxAttempts = $count * 10;
        
        while (count($spawnPoints) < $count && $attempts < $maxAttempts) {
            $attempts++;
            
            $point = [
                'x' => rand(-$mapSize/2, $mapSize/2),
                'y' => 0.5,
                'z' => rand(-$mapSize/2, $mapSize/2)
            ];
            
            // Check if point is inside an obstacle
            if ($this->isPointInObstacle($point, $obstacles)) {
                continue;
            }
            
            // Check if too close to other spawn points
            if ($this->isTooCloseToOthers($point, $spawnPoints, 5)) {
                continue;
            }
            
            $spawnPoints[] = $point;
        }
        
        return $spawnPoints;
    }
    
    /**
     * Check if point is inside an obstacle
     */
    private function isPointInObstacle(array $point, array $obstacles): bool
    {
        foreach ($obstacles as $obstacle) {
            $minX = $obstacle['x'] - $obstacle['width'] / 2;
            $maxX = $obstacle['x'] + $obstacle['width'] / 2;
            $minZ = $obstacle['z'] - $obstacle['depth'] / 2;
            $maxZ = $obstacle['z'] + $obstacle['depth'] / 2;
            
            if ($point['x'] >= $minX && $point['x'] <= $maxX &&
                $point['z'] >= $minZ && $point['z'] <= $maxZ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if point is too close to other points
     */
    private function isTooCloseToOthers(
        array $point,
        array $others,
        float $minDistance
    ): bool {
        foreach ($others as $other) {
            $distance = sqrt(
                pow($point['x'] - $other['x'], 2) +
                pow($point['z'] - $other['z'], 2)
            );
            
            if ($distance < $minDistance) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Create loot clusters (hotspots)
     */
    public function createLootClusters(ArenaMatch $match, int $clusterCount = 10): void
    {
        $lootService = app(LootService::class);
        
        for ($i = 0; $i < $clusterCount; $i++) {
            // Generate cluster center
            $center = [
                'x' => rand(-90, 90),
                'y' => 0.5,
                'z' => rand(-90, 90)
            ];
            
            // Spawn 5-10 items around center
            $itemCount = rand(5, 10);
            
            for ($j = 0; $j < $itemCount; $j++) {
                $offset = [
                    'x' => rand(-5, 5),
                    'y' => 0,
                    'z' => rand(-5, 5)
                ];
                
                $position = [
                    'x' => $center['x'] + $offset['x'],
                    'y' => $center['y'],
                    'z' => $center['z'] + $offset['z']
                ];
                
                $lootService->spawnLoot($match, $position);
            }
        }
    }
}

