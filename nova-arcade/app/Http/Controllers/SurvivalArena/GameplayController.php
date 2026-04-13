<?php

namespace App\Http\Controllers\SurvivalArena;

use App\Http\Controllers\Controller;
use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\PlayerState;
use App\Models\SurvivalArena\Weapon;
use App\Services\SurvivalArena\Combat\WeaponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameplayController extends Controller
{
    public function __construct(
        private WeaponService $weaponService
    ) {}

    /**
     * Get current game state
     */
    public function getState(ArenaMatch $match)
    {
        $playerStates = $match->playerStates()
            ->with('user:id,username')
            ->get()
            ->map(function ($state) {
                return [
                    'player_id' => $state->user_id,
                    'username' => $state->user->username,
                    'position' => $state->position,
                    'rotation' => $state->rotation,
                    'health' => $state->health,
                    'shield' => $state->shield,
                    'is_alive' => $state->health > 0
                ];
            });

        $currentZone = $match->safeZones()->current()->first();

        return response()->json([
            'timestamp' => now()->timestamp,
            'alive_players' => $match->getAlivePlayers(),
            'players' => $playerStates,
            'safe_zone' => $currentZone?->toGameData()
        ]);
    }

    /**
     * Player shoots weapon
     */
    public function shoot(ArenaMatch $match, Request $request)
    {
        $validated = $request->validate([
            'direction' => 'required|array',
            'direction.x' => 'required|numeric',
            'direction.y' => 'required|numeric',
            'direction.z' => 'required|numeric',
            'weapon_id' => 'required|exists:sa_weapons,id'
        ]);

        $playerState = PlayerState::where('match_id', $match->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Check if player is alive
        if ($playerState->health <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot shoot while dead'
            ], 400);
        }

        // Check if reloading
        if ($playerState->is_reloading) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot shoot while reloading'
            ], 400);
        }

        // Check ammo
        if ($playerState->ammo_current <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Out of ammo'
            ], 400);
        }

        $weapon = Weapon::findOrFail($validated['weapon_id']);

        try {
            $result = $this->weaponService->shoot(
                $playerState,
                $validated['direction'],
                $weapon
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pickup item
     */
    public function pickupItem(ArenaMatch $match, Request $request)
    {
        $validated = $request->validate([
            'loot_id' => 'required|integer'
        ]);

        $playerState = PlayerState::where('match_id', $match->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $loot = $match->lootSpawns()
            ->where('id', $validated['loot_id'])
            ->where('is_collected', false)
            ->firstOrFail();

        // Check distance to loot
        $distance = sqrt(
            pow($playerState->position['x'] - $loot->position['x'], 2) +
            pow($playerState->position['y'] - $loot->position['y'], 2) +
            pow($playerState->position['z'] - $loot->position['z'], 2)
        );

        if ($distance > 3) { // 3 meters pickup range
            return response()->json([
                'success' => false,
                'message' => 'Too far from item'
            ], 400);
        }

        // Collect the loot
        $loot->collect(Auth::user());

        // Handle different item types
        switch ($loot->item_type) {
            case 'weapon':
                $weapon = Weapon::find($loot->item_id);
                $playerState->pickupWeapon($weapon);
                break;
            
            case 'health':
                $playerState->heal(50);
                break;
            
            case 'shield':
                $playerState->addShield(50);
                break;
            
            case 'ammo':
                // Add ammo logic
                break;
        }

        return response()->json([
            'success' => true,
            'item_type' => $loot->item_type
        ]);
    }

    /**
     * Reload weapon
     */
    public function reload(ArenaMatch $match)
    {
        $playerState = PlayerState::where('match_id', $match->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($playerState->health <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reload while dead'
            ], 400);
        }

        $playerState->reload();

        // Schedule reload completion
        $weapon = $playerState->getCurrentWeapon();
        
        if ($weapon) {
            dispatch(function () use ($playerState) {
                $playerState->finishReload();
            })->delay(now()->addSeconds($weapon->reload_time));
        }

        return response()->json([
            'success' => true,
            'reload_time' => $weapon?->reload_time ?? 0
        ]);
    }

    /**
     * Switch weapon
     */
    public function switchWeapon(ArenaMatch $match, Request $request)
    {
        $validated = $request->validate([
            'slot' => 'required|integer|min:0|max:2'
        ]);

        $playerState = PlayerState::where('match_id', $match->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $playerState->switchWeapon($validated['slot']);

        return response()->json([
            'success' => true,
            'active_slot' => $validated['slot']
        ]);
    }

    /**
     * Use ability/item
     */
    public function useItem(ArenaMatch $match, Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'required|string|in:medkit,shield_boost,grenade'
        ]);

        $playerState = PlayerState::where('match_id', $match->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Handle different items
        switch ($validated['item_type']) {
            case 'medkit':
                $playerState->heal(50);
                break;
            
            case 'shield_boost':
                $playerState->addShield(50);
                break;
            
            case 'grenade':
                // Grenade logic
                break;
        }

        return response()->json(['success' => true]);
    }
}