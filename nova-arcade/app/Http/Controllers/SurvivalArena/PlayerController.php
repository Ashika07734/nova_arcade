<?php

namespace App\Http\Controllers\SurvivalArena;

use App\Http\Controllers\Controller;
use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\PlayerState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PlayerController extends Controller
{
    /**
     * Update player position (called frequently from client)
     */
    public function updatePosition(ArenaMatch $match, Request $request)
    {
        $validated = $request->validate([
            'position' => 'required|array',
            'position.x' => 'required|numeric',
            'position.y' => 'required|numeric',
            'position.z' => 'required|numeric',
            'rotation' => 'required|array',
            'rotation.x' => 'required|numeric',
            'rotation.y' => 'required|numeric',
            'rotation.z' => 'required|numeric',
            'velocity' => 'nullable|array',
            'is_sprinting' => 'nullable|boolean',
            'is_crouching' => 'nullable|boolean'
        ]);

        $playerState = PlayerState::where('match_id', $match->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Anti-cheat: Check for impossible movement speeds
        if (isset($validated['velocity'])) {
            $speed = sqrt(
                pow($validated['velocity']['x'], 2) +
                pow($validated['velocity']['y'], 2) +
                pow($validated['velocity']['z'], 2)
            );

            $maxSpeed = config('games.survival-arena.physics.player_speed') * 2; // Allow some margin

            if ($speed > $maxSpeed) {
                \Log::warning('Suspicious movement speed', [
                    'user_id' => Auth::id(),
                    'speed' => $speed,
                    'max_speed' => $maxSpeed
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid movement'
                ], 400);
            }
        }

        // Update state
        $playerState->update([
            'position' => $validated['position'],
            'rotation' => $validated['rotation'],
            'velocity' => $validated['velocity'] ?? $playerState->velocity,
            'is_sprinting' => $validated['is_sprinting'] ?? false,
            'is_crouching' => $validated['is_crouching'] ?? false,
            'last_updated' => now()
        ]);

        // Check if inside safe zone
        if (!$playerState->isInsideSafeZone()) {
            $currentZone = $match->safeZones()->current()->first();
            if ($currentZone) {
                $playerState->takeDamage($currentZone->damage_per_second);
            }
        }

        // Broadcast movement (handled by PlayerState model)

        return response()->json([
            'success' => true,
            'timestamp' => now()->timestamp
        ]);
    }

    /**
     * Get player's current state
     */
    public function getState(ArenaMatch $match)
    {
        $playerState = PlayerState::where('match_id', $match->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return response()->json([
            'position' => $playerState->position,
            'rotation' => $playerState->rotation,
            'health' => $playerState->health,
            'shield' => $playerState->shield,
            'stamina' => $playerState->stamina,
            'inventory' => $playerState->inventory,
            'active_weapon_slot' => $playerState->active_weapon_slot,
            'ammo_current' => $playerState->ammo_current,
            'ammo_reserve' => $playerState->ammo_reserve,
            'is_reloading' => $playerState->is_reloading
        ]);
    }

    /**
     * Get nearby players (for minimap, etc.)
     */
    public function getNearbyPlayers(ArenaMatch $match, Request $request)
    {
        $validated = $request->validate([
            'radius' => 'nullable|integer|min:10|max:200'
        ]);

        $radius = $validated['radius'] ?? 100;

        $playerState = PlayerState::where('match_id', $match->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $nearbyPlayers = PlayerState::where('match_id', $match->id)
            ->where('user_id', '!=', Auth::id())
            ->get()
            ->filter(function ($other) use ($playerState, $radius) {
                $distance = sqrt(
                    pow($other->position['x'] - $playerState->position['x'], 2) +
                    pow($other->position['z'] - $playerState->position['z'], 2)
                );
                return $distance <= $radius;
            })
            ->map(function ($other) use ($playerState) {
                $distance = sqrt(
                    pow($other->position['x'] - $playerState->position['x'], 2) +
                    pow($other->position['z'] - $playerState->position['z'], 2)
                );

                return [
                    'user_id' => $other->user_id,
                    'position' => $other->position,
                    'distance' => round($distance, 1)
                ];
            })
            ->values();

        return response()->json(['players' => $nearbyPlayers]);
    }
}