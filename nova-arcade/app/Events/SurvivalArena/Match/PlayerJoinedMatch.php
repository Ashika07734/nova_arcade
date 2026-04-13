<?php

namespace App\Events\SurvivalArena\Match;

use App\Models\SurvivalArena\ArenaMatch;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerJoinedMatch implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ArenaMatch $match,
        public User $user
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
        return 'player.joined';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'match_id' => $this->match->id,
            'player' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'avatar_url' => $this->user->avatar_url
            ],
            'current_players' => $this->match->current_players,
            'max_players' => $this->match->max_players,
            'timestamp' => now()->toISOString()
        ];
    }
}

