<?php

namespace App\Events\SurvivalArena\Player;

use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerPickedUpItem implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ArenaMatch $match,
        public int $playerId,
        public int $lootId,
        public string $itemType
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
        return 'player.picked.up.item';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'player_id' => $this->playerId,
            'loot_id' => $this->lootId,
            'item_type' => $this->itemType,
            'timestamp' => microtime(true)
        ];
    }
}

