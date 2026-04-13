<?php

namespace App\Jobs\SurvivalArena;

use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyPlayersMatchStarting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;

    public function __construct(
        public ArenaMatch $match,
        public int $secondsUntilStart
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->match->refresh();

        // Only notify if match is still waiting
        if ($this->match->status !== 'waiting') {
            return;
        }

        $players = $this->match->players()->with('user')->get();

        foreach ($players as $player) {
            // Send notification (implement your notification class)
            // Notification::send($player->user, new MatchStartingNotification($this->match, $this->secondsUntilStart));
        }

        // Broadcast to match channel
        broadcast(new \App\Events\SurvivalArena\Match\MatchStarting(
            $this->match,
            $this->secondsUntilStart
        ))->toOthers();
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['notifications', "match:{$this->match->id}"];
    }
}