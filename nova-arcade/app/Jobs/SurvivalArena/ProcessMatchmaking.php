<?php

namespace App\Jobs\SurvivalArena;

use App\Models\SurvivalArena\ArenaMatch;
use App\Services\SurvivalArena\Match\MatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMatchmaking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 1;

    public function __construct(
        public ArenaMatch $match
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->match->refresh();

        // Only process waiting matches
        if ($this->match->status !== 'waiting') {
            return;
        }

        $config = config('games.survival-arena.matchmaking');
        $minPlayers = $config['min_players'] ?? 2;
        $maxWaitTime = $config['max_wait_time'] ?? 60;

        // Calculate how long the match has been waiting
        $waitTime = now()->diffInSeconds($this->match->created_at);

        // Check if we have enough players
        if ($this->match->current_players >= $minPlayers) {
            
            // Start if we have min players and waited start_delay seconds
            $startDelay = $config['start_delay'] ?? 10;
            
            if ($waitTime >= $startDelay) {
                $this->startMatch();
                return;
            }
        }

        // Force start if max wait time exceeded
        if ($waitTime >= $maxWaitTime && $this->match->current_players >= 2) {
            Log::info('Force starting match due to max wait time', [
                'match_id' => $this->match->id,
                'players' => $this->match->current_players,
                'wait_time' => $waitTime
            ]);
            
            $this->startMatch();
            return;
        }

        // Delete empty matches after 2 minutes
        if ($this->match->current_players === 0 && $waitTime > 120) {
            Log::info('Deleting empty match', [
                'match_id' => $this->match->id
            ]);
            
            $this->match->delete();
            return;
        }

        // Re-schedule check in 5 seconds
        self::dispatch($this->match)->delay(now()->addSeconds(5));
    }

    /**
     * Start the match
     */
    private function startMatch(): void
    {
        try {
            if (($this->match->mode ?? $this->match->game_mode) === 'solo') {
                app(MatchService::class)->spawnBots(
                    $this->match,
                    $this->match->difficulty ?? 'easy',
                    (int) $this->match->bot_count
                );

                $this->match->refresh();
            }

            $this->match->start();

            Log::info('Match started via matchmaking', [
                'match_id' => $this->match->id,
                'players' => $this->match->current_players
            ]);

            // Dispatch game tick job
            ProcessGameTick::dispatch($this->match)
                ->delay(now()->addMilliseconds(17));

            // Dispatch safe zone update job
            UpdateSafeZone::dispatch($this->match)
                ->delay(now()->addSeconds(50)); // 10 seconds before first shrink

        } catch (\Exception $e) {
            Log::error('Failed to start match', [
                'match_id' => $this->match->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['matchmaking', "match:{$this->match->id}"];
    }
}

