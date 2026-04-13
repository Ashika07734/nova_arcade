<?php

namespace App\Http\Middleware;

use App\Models\SurvivalArena\ArenaMatch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckPlayerInMatch
{
    public function handle(Request $request, Closure $next, string $action = 'prevent'): Response
    {
        $userId = $request->user()->id;
        $cacheKey = "user:{$userId}:active_match";
        $activeMatchId = Cache::get($cacheKey);
        $activeMatch = null;

        if (!$activeMatchId) {
            $activeMatch = ArenaMatch::whereIn('status', ['waiting', 'starting', 'in_progress'])
                ->whereHas('players', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->first();

            if ($activeMatch) {
                $activeMatchId = $activeMatch->id;
                Cache::put($cacheKey, $activeMatchId, now()->addMinutes(30));
            }
        } else {
            $activeMatch = ArenaMatch::find($activeMatchId);
        }

        if ($action === 'prevent' && $activeMatch) {
            return response()->json([
                'error' => 'Already in a match',
                'message' => 'You are already in an active match',
                'active_match_id' => $activeMatch->id,
                'redirect' => $this->getRedirectUrl($activeMatch),
            ], 409);
        }

        if ($action === 'require' && !$activeMatch) {
            return response()->json([
                'error' => 'Not in a match',
                'message' => 'You must be in an active match to perform this action',
            ], 403);
        }

        if ($activeMatch) {
            $request->merge(['active_match' => $activeMatch]);
        }

        return $next($request);
    }

    private function getRedirectUrl(ArenaMatch $match): string
    {
        return match ($match->status) {
            'waiting', 'starting' => route('survival-arena.lobby', $match),
            'in_progress' => route('survival-arena.play', $match),
            default => route('survival-arena.matchmaking'),
        };
    }
}