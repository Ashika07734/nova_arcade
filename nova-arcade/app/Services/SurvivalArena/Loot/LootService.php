<?php

namespace App\Services\SurvivalArena\Loot;

use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\LootSpawn;
use App\Models\SurvivalArena\Weapon;

class LootService
{
    /**
     * Spawn loot at position
     */
    public function spawnLoot(ArenaMatch $match, array $position, ?string $itemType = null): LootSpawn
    {
        $itemType = $itemType ?? $this->getRandomItemType();
        $itemId = $this->getRandomItemId($itemType);
        
        return $match->lootSpawns()->create([
            'item_type' => $itemType,
            'item_id' => $itemId,
            'position' => $position,
            'spawned_at' => now()
        ]);
    }
    
    /**
     * Get random item type based on rarity weights
     */
    private function getRandomItemType(): string
    {
        $weights = [
            'weapon' => 40,
            'health' => 20,
            'shield' => 20,
            'ammo' => 15,
            'armor' => 5
        ];
        
        return $this->weightedRandom($weights);
    }
    
    /**
     * Get random item ID based on type
     */
    private function getRandomItemId(string $itemType): int
    {
        return match($itemType) {
            'weapon' => $this->getRandomWeaponId(),
            'health' => 1,
            'shield' => 1,
            'ammo' => 1,
            'armor' => rand(1, 3),
            default => 1
        };
    }
    
    /**
     * Get random weapon ID based on rarity
     */
    private function getRandomWeaponId(): int
    {
        $rarityWeights = [
            'common' => 50,
            'uncommon' => 30,
            'rare' => 15,
            'epic' => 4,
            'legendary' => 1
        ];
        
        $rarity = $this->weightedRandom($rarityWeights);
        
        $weapons = Weapon::where('rarity', $rarity)->pluck('id')->toArray();
        
        if (empty($weapons)) {
            $weapons = Weapon::pluck('id')->toArray();
        }
        
        return $weapons[array_rand($weapons)];
    }
    
    /**
     * Weighted random selection
     */
    private function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $rand = rand(1, $total);
        
        foreach ($weights as $key => $weight) {
            $rand -= $weight;
            if ($rand <= 0) {
                return $key;
            }
        }
        
        return array_key_first($weights);
    }
    
    /**
     * Get loot in radius
     */
    public function getLootInRadius(ArenaMatch $match, array $position, float $radius): array
    {
        return $match->lootSpawns()
            ->uncollected()
            ->get()
            ->filter(function ($loot) use ($position, $radius) {
                $distance = sqrt(
                    pow($loot->position['x'] - $position['x'], 2) +
                    pow($loot->position['z'] - $position['z'], 2)
                );
                return $distance <= $radius;
            })
            ->map(fn($loot) => $loot->toGameData())
            ->toArray();
    }
    
    /**
     * Cleanup old uncollected loot
     */
    public function cleanupOldLoot(ArenaMatch $match, int $minutesOld = 5): int
    {
        return $match->lootSpawns()
            ->uncollected()
            ->where('spawned_at', '<', now()->subMinutes($minutesOld))
            ->delete();
    }
}

