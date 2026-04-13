<?php

namespace App\Jobs\SurvivalArena;

use App\Models\SurvivalArena\ArenaMatch;
use App\Services\SurvivalArena\GameLoop\GameLoopService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGameTick implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 2; // 2 seconds timeout
    public $tries = 1; // Don't retry failed ticks
    public $maxExceptions = 1;

    public function __construct(
        public ArenaMatch $match
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GameLoopService $gameLoop): void
    {
        // Only process if match is still in progress
        if ($this->match->status !== 'in_progress') {
            Log::info('Match no longer in progress, stopping game loop', [
                'match_id' => $this->match->id,
                'status' => $this->match->status
            ]);
            return;
        }

        // Refresh match data from database
        $this->match->refresh();

        try {
            // Process single game tick
            $gameLoop->processTick($this->match);

            // Schedule next tick (16.67ms later for 60fps)
            $tickInterval = GameLoopService::getTickInterval();
            
            self::dispatch($this->match)
                ->delay(now()->addMilliseconds($tickInterval));

        } catch (\Exception $e) {
            Log::error('Game tick processing failed', [
                'match_id' => $this->match->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Try to continue anyway
            self::dispatch($this->match)
                ->delay(now()->addMilliseconds(20));
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Game tick job failed completely', [
            'match_id' => $this->match->id,
            'error' => $exception->getMessage()
        ]);

        // Optionally end the match
        if ($this->match->status === 'in_progress') {
            $this->match->update([
                'status' => 'finished',
                'ended_at' => now()
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['game-tick', "match:{$this->match->id}"];
    }
}

