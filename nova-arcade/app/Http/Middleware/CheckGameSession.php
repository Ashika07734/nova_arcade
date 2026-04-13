<?php

namespace App\Http\Middleware;

use App\Models\SurvivalArena\ArenaMatch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckGameSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $matchId = $request->route('match')?->id ?? $request->input('match_id');

        if (!$matchId) {
            return response()->json([
                'error' => 'No match ID provided',
            ], 400);
        }

        $match = ArenaMatch::find($matchId);

        if (!$match) {
            return response()->json([
                'error' => 'Match not found',
            ], 404);
        }

        $isParticipant = $match->players()
            ->where('user_id', $request->user()->id)
            ->exists();

        if (!$isParticipant) {
            return response()->json([
                'error' => 'You are not a participant in this match',
                'message' => 'Access denied',
            ], 403);
        }

        if ($match->status === 'finished') {
            return response()->json([
                'error' => 'This match has already ended',
                'redirect' => route('survival-arena.results', $match),
            ], 410);
        }

        $request->merge(['current_match' => $match]);

        return $next($request);
    }
}