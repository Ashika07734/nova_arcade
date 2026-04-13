<?php

namespace App\Events\SurvivalArena\Match;

use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchEnded implements ShouldBroadcast
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
            new PrivateChannel('survival-arena.match.' . $this->match->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'match.ended';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $winner = $this->match->winner;
        $duration = $this->match->started_at && $this->match->ended_at
            ? $this->match->ended_at->diffInSeconds($this->match->started_at)
            : 0;

        // Get final rankings
        $rankings = $this->match->players()
            ->with('user:id,username')
            ->orderBy('placement')
            ->get()
            ->map(function ($player) {
                return [
                    'user_id' => $player->user_id,
                    'username' => $player->user->username,
                    'placement' => $player->placement,
                    'kills' => $player->kills,
                    'damage_dealt' => $player->damage_dealt,
                    'survival_time' => $player->survival_time,
                    'xp_earned' => $player->xp_earned
                ];
            });

        return [
            'match_id' => $this->match->id,
            'winner_id' => $winner?->id,
            'winner_name' => $winner?->username,
            'duration' => $duration,
            'ended_at' => $this->match->ended_at->toISOString(),
            'rankings' => $rankings
        ];
    }
}

