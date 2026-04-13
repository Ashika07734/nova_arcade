<?php

namespace App\Events\SurvivalArena\SafeZone;

use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\SafeZone;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SafeZoneShrinking implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ArenaMatch $match,
        public SafeZone $safeZone,
        public int $secondsRemaining
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('survival-arena.match.' . $this->match->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'safe.zone.shrinking';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'phase' => $this->safeZone->phase,
            'seconds_remaining' => $this->secondsRemaining,
            'message' => "Zone shrinking in {$this->secondsRemaining} seconds!",
            'timestamp' => microtime(true)
        ];
    }
}

