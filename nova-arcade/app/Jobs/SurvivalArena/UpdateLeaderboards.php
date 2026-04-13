<?php

namespace App\Jobs\SurvivalArena;

use App\Services\SurvivalArena\Stats\LeaderboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateLeaderboards implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 2;

    /**
     * Execute the job.
     */
    public function handle(LeaderboardService $leaderboardService): void
    {
        Log::info('Starting leaderboard update');

        try {
            $leaderboardService->updateAllLeaderboards();

            Log::info('Leaderboards updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update leaderboards', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['leaderboards', 'stats'];
    }
}

