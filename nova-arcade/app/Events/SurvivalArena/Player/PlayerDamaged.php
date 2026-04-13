<?php

namespace App\Events\SurvivalArena\Player;

use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerDamaged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ArenaMatch $match,
        public int $victimId,
        public int $damage,
        public int $newHealth,
        public int $newShield,
        public ?int $attackerId = null
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
        return 'player.damaged';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'victim_id' => $this->victimId,
            'attacker_id' => $this->attackerId,
            'damage' => $this->damage,
            'health' => $this->newHealth,
            'shield' => $this->newShield,
            'timestamp' => microtime(true)
        ];
    }
}

