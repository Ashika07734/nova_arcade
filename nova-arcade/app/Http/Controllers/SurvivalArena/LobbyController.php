<?php

namespace App\Http\Controllers\SurvivalArena;

use App\Http\Controllers\Controller;
use App\Jobs\SurvivalArena\ProcessGameTick;
use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LobbyController extends Controller
{
    public function show(ArenaMatch $match)
    {
        $isPlayer = $match->players()
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isPlayer) {
            abort(403, 'You are not in this match');
        }

        $players = $match->players()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        $isHost = $players->first()?->user_id === Auth::id();

        return view('survival-arena.lobby', compact('match', 'players', 'isHost'));
    }

    public function toggleReady(ArenaMatch $match)
    {
        $match->players()
            ->where('user_id', Auth::id())
            ->firstOrFail();

        try {
            broadcast(new \App\Events\SurvivalArena\Match\PlayerReadyStatusChanged(
                $match,
                Auth::id(),
                true
            ))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Ready-status broadcast failed', [
                'match_id' => $match->id,
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function start(ArenaMatch $match)
    {
        $host = $match->players()->oldest()->first();

        if (!$host || $host->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the host can start the match',
            ], 403);
        }

        $minPlayersByMode = config('games.survival-arena.matchmaking.min_players_by_mode', [
            'solo' => 1,
            'duo' => 2,
            'squad' => 4,
        ]);

        $minPlayers = (int) ($minPlayersByMode[$match->game_mode] ?? config('games.survival-arena.matchmaking.min_players', 2));

        if ($match->current_players < $minPlayers) {
            return response()->json([
                'success' => false,
                'message' => "Need at least {$minPlayers} players to start",
            ], 400);
        }

        try {
            $match->start();

            ProcessGameTick::dispatch($match)->delay(now()->addMilliseconds(17));

            return response()->json([
                'success' => true,
                'redirect' => route('survival-arena.play', $match),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function leave(ArenaMatch $match)
    {
        try {
            $match->removePlayer(Auth::user());

            return redirect()
                ->route('survival-arena.matchmaking')
                ->with('success', 'Left the match');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function kickPlayer(ArenaMatch $match, Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $host = $match->players()->oldest()->first();

        if (!$host || $host->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the host can kick players',
            ], 403);
        }

        if ($request->user_id == Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot kick yourself',
            ], 400);
        }

        $userToKick = \App\Models\User::find($request->user_id);
        $match->removePlayer($userToKick);

        return response()->json(['success' => true]);
    }

    public function getData(ArenaMatch $match)
    {
        $players = $match->players()
            ->with('user:id,name,username,avatar')
            ->get()
            ->map(function ($player) {
                return [
                    'user_id' => $player->user_id,
                    'username' => $player->user->username,
                    'avatar_url' => $player->user->avatar_url,
                    'ready' => false, // Add ready column if needed
                    'joined_at' => $player->joined_at->diffForHumans()
                ];
            });

        return response()->json([
            'match' => [
                'id' => $match->id,
                'match_code' => $match->match_code,
                'status' => $match->status,
                'current_players' => $match->current_players,
                'max_players' => $match->max_players,
                'game_mode' => $match->game_mode,
            ],
            'players' => $players,
        ]);
    }
}