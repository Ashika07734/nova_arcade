<?php

namespace App\Http\Middleware;

use App\Models\SurvivalArena\PlayerState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidatePlayerState
{
    public function handle(Request $request, Closure $next): Response
    {
        $matchId = $request->route('match')?->id;
        $userId = $request->user()->id;

        if (!$matchId) {
            return $next($request);
        }

        $playerState = PlayerState::where('match_id', $matchId)
            ->where('user_id', $userId)
            ->first();

        if (!$playerState) {
            return response()->json([
                'error' => 'Player state not found',
                'message' => 'You have not joined this match yet',
            ], 404);
        }

        $actionRequiresAlive = in_array($request->route()->getActionMethod(), [
            'shoot',
            'pickupItem',
            'reload',
            'switchWeapon',
        ], true);

        if ($actionRequiresAlive && $playerState->health <= 0) {
            return response()->json([
                'error' => 'Player is dead',
                'message' => 'You cannot perform this action while dead',
            ], 403);
        }

        if ($request->has('position')) {
            $position = $request->input('position');

            if (!$this->isPositionValid($position)) {
                Log::warning('Invalid position detected', [
                    'user_id' => $userId,
                    'match_id' => $matchId,
                    'position' => $position,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => 'Invalid position',
                    'message' => 'Position validation failed',
                ], 400);
            }

            if (!$this->isMovementValid($playerState, $position)) {
                Log::warning('Suspicious movement detected', [
                    'user_id' => $userId,
                    'match_id' => $matchId,
                    'old_position' => $playerState->position,
                    'new_position' => $position,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => 'Suspicious movement',
                    'message' => 'Movement validation failed',
                ], 400);
            }
        }

        $request->merge(['player_state' => $playerState]);

        return $next($request);
    }

    private function isPositionValid(array $position): bool
    {
        $mapSize = 200;

        if (abs($position['x']) > $mapSize / 2 || abs($position['z']) > $mapSize / 2) {
            return false;
        }

        if ($position['y'] < 0 || $position['y'] > 50) {
            return false;
        }

        return true;
    }

    private function isMovementValid(PlayerState $playerState, array $newPosition): bool
    {
        $oldPosition = $playerState->position;
        $lastUpdate = $playerState->last_updated;
        $timeDiff = max(now()->diffInSeconds($lastUpdate), 0.1);

        $distance = sqrt(
            pow($newPosition['x'] - $oldPosition['x'], 2) +
            pow($newPosition['y'] - $oldPosition['y'], 2) +
            pow($newPosition['z'] - $oldPosition['z'], 2)
        );

        $maxSpeed = 10;
        $maxDistance = $maxSpeed * $timeDiff;

        return $distance <= ($maxDistance * 1.5);
    }
}

