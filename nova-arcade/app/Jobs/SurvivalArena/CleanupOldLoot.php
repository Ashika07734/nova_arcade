<?php

namespace App\Jobs\SurvivalArena;

use App\Models\SurvivalArena\ArenaMatch;
use App\Services\SurvivalArena\Loot\LootService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupOldLoot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ArenaMatch $match
    ) {}

    /**
     * Execute the job.
     */
    public function handle(LootService $lootService): void
    {
        $this->match->refresh();

        if ($this->match->status !== 'in_progress') {
            return;
        }

        // Cleanup loot older than 5 minutes
        $deleted = $lootService->cleanupOldLoot($this->match, 5);

        // Re-schedule if match still in progress
        if ($this->match->status === 'in_progress') {
            self::dispatch($this->match)->delay(now()->addMinutes(1));
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['loot-cleanup', "match:{$this->match->id}"];
    }
}