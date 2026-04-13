<?php

namespace App\Services\SurvivalArena\Match;

use App\Models\SurvivalArena\ArenaMatch;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MatchmakingService
{
    public function findOrCreateMatch(User $user, string $gameMode = 'solo'): ArenaMatch
    {
        $existingMatch = $this->getUserActiveMatch($user, $gameMode);
        if ($existingMatch) {
            return $existingMatch;
        }

        $match = $this->findAvailableMatch($gameMode, $user);
        if ($match) {
            $match->addPlayer($user);
            return $match;
        }

        return $this->createMatch($user, $gameMode);
    }

    private function getUserActiveMatch(User $user, string $requestedMode): ?ArenaMatch
    {
        $activeMatch = ArenaMatch::active()
            ->whereHas('players', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$activeMatch) {
            return null;
        }

        // Always keep the player inside an active in-progress match.
        if ($activeMatch->status === 'in_progress') {
            $staleWindow = (int) config('games.survival-arena.matchmaking.stale_in_progress_minutes', 30);
            if ($activeMatch->started_at && $activeMatch->started_at->diffInMinutes(now()) >= $staleWindow) {
                return null;
            }

            return $activeMatch;
        }

        // Reuse waiting/starting match only when the requested mode matches.
        if ($activeMatch->game_mode === $requestedMode) {
            return $activeMatch;
        }

        // Player requested a different mode while still queued: leave old queue and continue.
        $activeMatch->removePlayer($user);
        $activeMatch->refresh();

        if ($activeMatch->current_players <= 0) {
            $activeMatch->delete();
        }

        return null;
    }

    private function findAvailableMatch(string $gameMode, User $user): ?ArenaMatch
    {
        return ArenaMatch::where('status', 'waiting')
            ->where('game_mode', $gameMode)
            ->whereColumn('current_players', '<', 'max_players')
            ->whereDoesntHave('players', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('current_players', 'desc')
            ->first();
    }

    private function createMatch(User $user, string $gameMode): ArenaMatch
    {
        $match = ArenaMatch::create([
            'game_mode' => $gameMode,
            'max_players' => $this->getMaxPlayers($gameMode),
            'status' => 'waiting',
        ]);

        $match->addPlayer($user);

        Cache::tags(['active_matches'])->put(
            "match:{$match->id}",
            $match,
            now()->addHours(1)
        );

        return $match;
    }

    private function getMaxPlayers(string $gameMode): int
    {
        return match ($gameMode) {
            'solo' => config('games.survival-arena.matchmaking.max_players', 50),
            'duo' => 50,
            'squad' => 40,
            default => 50,
        };
    }

    public function removeFromQueue(User $user): bool
    {
        $match = $this->getUserActiveMatch($user);

        if ($match && $match->status === 'waiting') {
            $match->removePlayer($user);

            if ($match->current_players === 0) {
                $match->delete();
            }

            return true;
        }

        return false;
    }

    public function getStats(): array
    {
        return [
            'active_matches' => ArenaMatch::active()->count(),
            'waiting_matches' => ArenaMatch::waiting()->count(),
            'total_players_queued' => ArenaMatch::waiting()->sum('current_players'),
            'in_progress_matches' => ArenaMatch::inProgress()->count(),
            'total_players_playing' => ArenaMatch::inProgress()->sum('current_players'),
        ];
    }
}