<?php

namespace App\Events\SurvivalArena\Player;

use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerShot implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ArenaMatch $match,
        public int $playerId,
        public array $direction,
        public int $weaponId
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
        return 'player.shot';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $weapon = \App\Models\SurvivalArena\Weapon::find($this->weaponId);

        return [
            'player_id' => $this->playerId,
            'direction' => $this->direction,
            'weapon_id' => $this->weaponId,
            'weapon_name' => $weapon?->name ?? 'Unknown',
            'timestamp' => microtime(true)
        ];
    }
}

