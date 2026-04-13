<?php

namespace App\Jobs\SurvivalArena;

use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredMatches implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting match cleanup');

        // Delete old finished matches (older than 7 days)
        $deletedFinished = ArenaMatch::where('status', 'finished')
            ->where('ended_at', '<', now()->subDays(7))
            ->delete();

        // Delete abandoned waiting matches (older than 30 minutes)
        $deletedWaiting = ArenaMatch::where('status', 'waiting')
            ->where('created_at', '<', now()->subMinutes(30))
            ->where('current_players', 0)
            ->delete();

        // Force end stuck in-progress matches (older than 30 minutes)
        $stuckMatches = ArenaMatch::where('status', 'in_progress')
            ->where('started_at', '<', now()->subMinutes(30))
            ->get();

        foreach ($stuckMatches as $match) {
            $match->update([
                'status' => 'finished',
                'ended_at' => now()
            ]);

            Log::warning('Force ended stuck match', [
                'match_id' => $match->id,
                'started_at' => $match->started_at
            ]);
        }

        Log::info('Match cleanup completed', [
            'deleted_finished' => $deletedFinished,
            'deleted_waiting' => $deletedWaiting,
            'force_ended' => $stuckMatches->count()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['cleanup', 'maintenance'];
    }
}

