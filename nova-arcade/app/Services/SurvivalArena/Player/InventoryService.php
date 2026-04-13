<?php

namespace App\Services\SurvivalArena\Player;

use App\Models\SurvivalArena\PlayerState;
use App\Models\SurvivalArena\Weapon;

class InventoryService
{
    private int $maxWeapons = 2;
    
    /**
     * Add weapon to inventory
     */
    public function addWeapon(PlayerState $player, Weapon $weapon): bool
    {
        $inventory = $player->inventory ?? [];
        
        // Check if inventory is full
        if (count($inventory) >= $this->maxWeapons) {
            // Replace current weapon
            $inventory[$player->active_weapon_slot] = [
                'weapon_id' => $weapon->id,
                'ammo' => $weapon->magazine_size
            ];
        } else {
            // Add to inventory
            $inventory[] = [
                'weapon_id' => $weapon->id,
                'ammo' => $weapon->magazine_size
            ];
        }
        
        $player->inventory = $inventory;
        $player->ammo_current = $weapon->magazine_size;
        $player->ammo_reserve = $weapon->magazine_size * 3;
        $player->save();
        
        return true;
    }
    
    /**
     * Remove weapon from inventory
     */
    public function removeWeapon(PlayerState $player, int $slot): bool
    {
        $inventory = $player->inventory ?? [];
        
        if (!isset($inventory[$slot])) {
            return false;
        }
        
        unset($inventory[$slot]);
        $player->inventory = array_values($inventory); // Re-index
        $player->save();
        
        return true;
    }
    
    /**
     * Switch active weapon
     */
    public function switchWeapon(PlayerState $player, int $slot): bool
    {
        $inventory = $player->inventory ?? [];
        
        if (!isset($inventory[$slot])) {
            return false;
        }
        
        $player->active_weapon_slot = $slot;
        
        // Update ammo
        $weapon = Weapon::find($inventory[$slot]['weapon_id']);
        if ($weapon) {
            $player->ammo_current = $inventory[$slot]['ammo'] ?? $weapon->magazine_size;
        }
        
        $player->save();
        
        return true;
    }
    
    /**
     * Add ammo
     */
    public function addAmmo(PlayerState $player, int $amount): void
    {
        $player->ammo_reserve = min(
            $player->ammo_reserve + $amount,
            999 // Max ammo
        );
        $player->save();
    }
}

