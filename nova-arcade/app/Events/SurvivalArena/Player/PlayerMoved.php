<?php

namespace App\Events\SurvivalArena\Player;

use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ArenaMatch $match,
        public int $playerId,
        public array $position,
        public array $rotation,
        public ?array $velocity = null
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
        return 'player.moved';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'player_id' => $this->playerId,
            'position' => $this->position,
            'rotation' => $this->rotation,
            'velocity' => $this->velocity,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Determine if this event should be broadcast.
     */
    public function shouldBroadcast(): bool
    {
        return $this->match->status === 'in_progress';
    }
}

