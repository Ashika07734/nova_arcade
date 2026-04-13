<?php

namespace App\Jobs\SurvivalArena;

use App\Models\SurvivalArena\ArenaMatch;
use App\Services\SurvivalArena\Loot\LootService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SpawnLoot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 2;

    public function __construct(
        public ArenaMatch $match,
        public array $position,
        public ?string $itemType = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(LootService $lootService): void
    {
        $this->match->refresh();

        // Only spawn loot if match is in progress
        if ($this->match->status !== 'in_progress') {
            return;
        }

        try {
            $loot = $lootService->spawnLoot(
                $this->match,
                $this->position,
                $this->itemType
            );

            Log::info('Loot spawned', [
                'match_id' => $this->match->id,
                'loot_id' => $loot->id,
                'item_type' => $loot->item_type,
                'position' => $this->position
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to spawn loot', [
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
        return ['loot', "match:{$this->match->id}"];
    }
}