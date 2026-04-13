<?php

namespace App\Jobs\SurvivalArena;

use App\Models\SurvivalArena\ArenaMatch;
use App\Services\SurvivalArena\Match\MatchService;
use App\Services\SurvivalArena\Stats\StatsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateMatchResults implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 3;

    public function __construct(
        public ArenaMatch $match
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        MatchService $matchService,
        StatsService $statsService
    ): void {
        $this->match->refresh();

        Log::info('Calculating match results', [
            'match_id' => $this->match->id
        ]);

        try {
            // Calculate and get results
            $results = $matchService->calculateResults($this->match);

            // Update user stats for each player
            foreach ($this->match->players as $player) {
                $statsService->updateUserStats($player->user, $player);
            }

            // Update leaderboards (dispatch separate job)
            UpdateLeaderboards::dispatch()->delay(now()->addMinutes(1));

            // Process achievements (if implemented)
            // ProcessAchievements::dispatch($this->match);

            Log::info('Match results calculated successfully', [
                'match_id' => $this->match->id,
                'players_processed' => count($results)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to calculate match results', [
                'match_id' => $this->match->id,
                'error' => $e->getMessage()
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['match-results', "match:{$this->match->id}"];
    }
}

