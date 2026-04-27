<?php

namespace App\Services\SurvivalArena\GameLoop;

use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\PlayerState;
use App\Services\SurvivalArena\Bot\BotService;
use App\Services\SurvivalArena\Combat\HitDetectionService;
use App\Services\SurvivalArena\Player\MovementService;
use App\Services\SurvivalArena\Match\SafeZoneService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class GameLoopService
{
    const TICK_RATE = 60; // Updates per second
    const TICK_INTERVAL = 1000 / self::TICK_RATE; // ~16.67ms
    
    public function __construct(
        private MovementService $movementService,
        private HitDetectionService $hitDetectionService,
        private SafeZoneService $safeZoneService,
        private PhysicsService $physicsService,
        private CollisionService $collisionService,
        private BotService $botService
    ) {}
    
    /**
     * Main game tick - called 60 times per second
     */
    public function processTick(ArenaMatch $match): void
    {
        if ($match->status !== 'in_progress') {
            return;
        }

        $maxDuration = (int) config('games.survival-arena.timing.max_duration_seconds', 300);
        if ($match->started_at && now()->diffInSeconds($match->started_at) >= $maxDuration) {
            $winner = $match->players()
                ->where('is_bot', false)
                ->where('is_alive', true)
                ->orderByDesc('kills')
                ->first();

            $match->end($winner?->user);
            return;
        }
        
        try {
            // Get all active player states
            $players = $this->getPlayerStates($match);
            
            if ($players->isEmpty()) {
                return;
            }
            
            // 1. Update player physics
            foreach ($players as $player) {
                $this->physicsService->applyGravity($player);
                $this->physicsService->updateVelocity($player);
                $this->movementService->updatePosition($player);
            }

            // 1.5 Update AI bots (solo mode).
            $this->botService->updateBots($match, $players);
            
            // 2. Check collisions
            $this->collisionService->checkCollisions($match, $players);
            
            // 3. Update safe zone damage
            $this->safeZoneService->applyDamageToPlayersOutsideZone($match, $players);
            
            // 4. Check win condition
            if ($this->checkWinCondition($match)) {
                return; // Match ended
            }
            
            // 5. Cache updated states for fast retrieval
            $this->cacheGameState($match, $players);

            // Persist all updates after physics/collision and zone processing.
            foreach ($players as $player) {
                $player->save();
            }
            
        } catch (\Exception $e) {
            \Log::error('Game loop error', [
                'match_id' => $match->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get player states from cache or database
     */
    private function getPlayerStates(ArenaMatch $match): Collection
    {
        $cacheKey = "match:{$match->id}:players";
        
        return Cache::remember($cacheKey, now()->addSeconds(1), function () use ($match) {
            $states = PlayerState::where('match_id', $match->id)->get();

            return $states->filter(function (PlayerState $state) use ($match) {
                if ($state->is_bot) {
                    return $match->players()
                        ->where('is_bot', true)
                        ->where('bot_name', $state->bot_name)
                        ->where('is_alive', true)
                        ->exists();
                }

                if (!$state->user_id) {
                    return false;
                }

                return $match->players()
                    ->where('user_id', $state->user_id)
                    ->where('is_alive', true)
                    ->exists();
            })->values();
        });
    }
    
    /**
     * Check win condition
     */
    private function checkWinCondition(ArenaMatch $match): bool
    {
        $alivePlayers = $match->getAlivePlayers();
        
        if ($alivePlayers <= 1) {
            $match->checkWinCondition();
            return true;
        }
        
        return false;
    }
    
    /**
     * Cache game state for fast retrieval
     */
    private function cacheGameState(ArenaMatch $match, Collection $players): void
    {
        $state = [
            'timestamp' => microtime(true),
            'alive_players' => $match->getAlivePlayers(),
            'players' => $players->map(function ($player) {
                return [
                    'user_id' => $player->user_id,
                    'position' => $player->position,
                    'rotation' => $player->rotation,
                    'velocity' => $player->velocity,
                    'health' => $player->health,
                    'shield' => $player->shield
                ];
            })->toArray()
        ];
        
        Cache::put(
            "match:{$match->id}:state",
            $state,
            now()->addSeconds(5)
        );
    }
    
    /**
     * Get tick interval in milliseconds
     */
    public static function getTickInterval(): int
    {
        return self::TICK_INTERVAL;
    }
}

