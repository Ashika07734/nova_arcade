<?php

namespace App\Events\SurvivalArena\Match;

use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ArenaMatch $match
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('matches'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'match.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'match' => [
                'id' => $this->match->id,
                'match_code' => $this->match->match_code,
                'game_mode' => $this->match->game_mode,
                'max_players' => $this->match->max_players,
                'current_players' => $this->match->current_players,
                'status' => $this->match->status,
                'created_at' => $this->match->created_at->toISOString()
            ]
        ];
    }
}