<?php

namespace App\Events\SurvivalArena\Player;

use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerKilled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ArenaMatch $match,
        public int $killerId,
        public int $victimId,
        public string $weaponName,
        public bool $headshot = false
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
        return 'player.killed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $killer = \App\Models\User::find($this->killerId);
        $victim = \App\Models\User::find($this->victimId);

        return [
            'killer_id' => $this->killerId,
            'killer_name' => $killer?->username ?? 'Unknown',
            'victim_id' => $this->victimId,
            'victim_name' => $victim?->username ?? 'Unknown',
            'weapon_name' => $this->weaponName,
            'headshot' => $this->headshot,
            'alive_players' => $this->match->getAlivePlayers(),
            'timestamp' => microtime(true)
        ];
    }
}

