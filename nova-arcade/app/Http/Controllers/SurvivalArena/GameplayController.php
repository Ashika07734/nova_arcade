<?php

namespace App\Http\Controllers\SurvivalArena;

use App\Http\Controllers\Controller;
use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\PlayerState;
use App\Models\SurvivalArena\Weapon;
use App\Services\SurvivalArena\Match\MatchService;
use App\Services\SurvivalArena\Combat\WeaponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class GameplayController extends Controller
{
    public function __construct(
        private WeaponService $weaponService,
        private MatchService $matchService
    ) {}

    /**
     * Get current game state
     */
    public function getState(ArenaMatch $match)
    {
        $this->ensureSoloBotsInitialized($match);

        $currentPlayer = $match->players()
            ->where('user_id', Auth::id())
            ->first();

        $playerStates = $match->playerStates()
            ->with('user:id,username')
            ->get()
            ->map(function ($state) {
                return [
                    'state_id' => $state->id,
                    'player_id' => $state->user_id,
                    'username' => $state->is_bot
                        ? ($state->bot_name ?: 'BOT')
                        : ($state->user->username ?? 'Unknown'),
                    'is_bot' => (bool) $state->is_bot,
                    'bot_difficulty' => $state->bot_difficulty,
                    'position' => $state->position,
                    'rotation' => $state->rotation,
                    'health' => $state->health,
                    'shield' => $state->shield,
                    'ammo_current' => $state->ammo_current,
                    'ammo_reserve' => $state->ammo_reserve,
                    'is_alive' => $state->health > 0
                ];
            });

        $currentZone = $match->safeZones()->current()->first();
        $killFeed = Cache::get("match:{$match->id}:kill_feed", []);

        return response()->json([
            'timestamp' => now()->timestamp,
            'alive_players' => $match->getAlivePlayers(),
            'alive_bots' => $match->getAliveBots(),
            'alive_humans' => $match->getAliveHumans(),
            'difficulty' => $match->difficulty,
            'bot_count' => $match->bot_count,
            'status' => $match->status,
            'kill_feed' => $killFeed,
            'map_data' => $match->map_data,
            'player_summary' => $currentPlayer ? [
                'kills' => $currentPlayer->kills,
                'headshots' => $currentPlayer->headshots,
                'placement' => $currentPlayer->placement,
                'survival_time' => $currentPlayer->survival_time,
                'score' => $currentPlayer->score,
                'shots_fired' => $currentPlayer->shots_fired,
                'shots_hit' => $currentPlayer->shots_hit,
                'accuracy' => $currentPlayer->accuracy,
            ] : null,
            'players' => $playerStates,
            'safe_zone' => $currentZone?->toGameData()
        ]);
    }

    private function ensureSoloBotsInitialized(ArenaMatch $match): void
    {
        if (($match->mode ?? $match->game_mode) !== 'solo' || $match->status !== 'in_progress') {
            return;
        }

        $desiredBotCount = max(0, (int) ($match->bot_count ?? 0));
        if ($desiredBotCount <= 0) {
            return;
        }

        $existingBotPlayers = $match->players()->where('is_bot', true)->count();
        if ($existingBotPlayers < $desiredBotCount) {
            $this->matchService->spawnBots($match, $match->difficulty ?? 'easy', $desiredBotCount);
            $match->refresh();
        }

        $botPlayers = $match->players()
            ->where('is_bot', true)
            ->orderBy('id')
            ->get();

        $spawnPoints = (array) data_get($match->map_data, 'spawn_points.bots', []);
        $fallbackSpawns = [
            ['x' => -38, 'y' => 1, 'z' => -24],
            ['x' => 28, 'y' => 1, 'z' => -14],
            ['x' => -6, 'y' => 1, 'z' => -42],
            ['x' => 44, 'y' => 1, 'z' => 42],
        ];

        foreach ($botPlayers as $index => $botPlayer) {
            $spawn = $spawnPoints[$index % max(1, count($spawnPoints))] ?? $fallbackSpawns[$index % count($fallbackSpawns)];

            PlayerState::firstOrCreate(
                [
                    'match_id' => $match->id,
                    'is_bot' => true,
                    'bot_name' => $botPlayer->bot_name,
                ],
                [
                    'user_id' => null,
                    'bot_difficulty' => $botPlayer->bot_difficulty,
                    'position' => [
                        'x' => (float) ($spawn['x'] ?? 0),
                        'y' => (float) ($spawn['y'] ?? 1),
                        'z' => (float) ($spawn['z'] ?? 0),
                    ],
                    'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
                    'velocity' => ['x' => 0, 'y' => 0, 'z' => 0],
                    'health' => 100,
                    'shield' => 0,
                    'stamina' => 100,
                    'inventory' => [],
                    'ammo_current' => 999,
                    'ammo_reserve' => 9999,
                    'last_updated' => now(),
                ]
            );
        }

        $humanState = PlayerState::where('match_id', $match->id)
            ->where('is_bot', false)
            ->where('user_id', Auth::id())
            ->first();

        if (!$humanState) {
            return;
        }

        $botStates = PlayerState::where('match_id', $match->id)
            ->where('is_bot', true)
            ->where('health', '>', 0)
            ->get();

        if ($botStates->isEmpty()) {
            return;
        }

        $playerPos = $humanState->position ?? ['x' => 0, 'z' => 0];
        $nearestDistance = $botStates->min(function (PlayerState $state) use ($playerPos) {
            $pos = $state->position ?? ['x' => 0, 'z' => 0];
            $dx = ($pos['x'] ?? 0) - ($playerPos['x'] ?? 0);
            $dz = ($pos['z'] ?? 0) - ($playerPos['z'] ?? 0);
            return sqrt(($dx * $dx) + ($dz * $dz));
        });

        if ($nearestDistance !== null && $nearestDistance <= 55) {
            return;
        }

        $nextRepositionAtKey = "match:{$match->id}:bots_reposition_next_at";
        $nextRepositionAt = (int) Cache::get($nextRepositionAtKey, 0);
        if (time() < $nextRepositionAt) {
            return;
        }

        $targetBot = $botStates->first();
        if ($targetBot) {
            $offsets = [
                ['x' => 14, 'z' => 10],
                ['x' => -16, 'z' => 9],
                ['x' => 10, 'z' => -14],
                ['x' => -12, 'z' => -12],
            ];
            $pick = $offsets[array_rand($offsets)];

            $targetBot->position = [
                'x' => ($playerPos['x'] ?? 0) + $pick['x'],
                'y' => max(1, (float) (($playerPos['y'] ?? 1))),
                'z' => ($playerPos['z'] ?? 0) + $pick['z'],
            ];
            $targetBot->velocity = ['x' => 0, 'y' => 0, 'z' => 0];
            $targetBot->rotation = ['x' => 0, 'y' => 0, 'z' => 0];
            $targetBot->last_updated = now();
            $targetBot->save();
            Cache::put($nextRepositionAtKey, time() + 8, now()->addMinutes(10));
        }
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