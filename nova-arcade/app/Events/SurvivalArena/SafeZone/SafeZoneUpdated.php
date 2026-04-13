<?php

namespace App\Events\SurvivalArena\SafeZone;

use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\SafeZone;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SafeZoneUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ArenaMatch $match,
        public SafeZone $safeZone
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
        return 'safe.zone.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'phase' => $this->safeZone->phase,
            'center' => $this->safeZone->center,
            'radius' => $this->safeZone->radius,
            'damage_per_second' => $this->safeZone->damage_per_second,
            'time_remaining' => $this->safeZone->getTimeRemaining(),
            'ends_at' => $this->safeZone->ends_at->toISOString(),
            'timestamp' => microtime(true)
        ];
    }
}

