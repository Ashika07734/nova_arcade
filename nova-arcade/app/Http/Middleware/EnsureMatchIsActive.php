<?php

namespace App\Http\Middleware;

use App\Models\SurvivalArena\ArenaMatch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMatchIsActive
{
    public function handle(Request $request, Closure $next, string $requiredStatus = 'in_progress'): Response
    {
        $match = $request->route('match');

        if (!$match instanceof ArenaMatch) {
            return response()->json([
                'error' => 'Invalid match',
            ], 400);
        }

        $match->refresh();
        $allowedStatuses = explode('|', $requiredStatus);

        if (!in_array($match->status, $allowedStatuses, true)) {
            return response()->json([
                'error' => 'Match is not in the correct state',
                'current_status' => $match->status,
                'required_status' => $allowedStatuses,
                'message' => $this->getStatusMessage($match->status),
            ], 409);
        }

        return $next($request);
    }

    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'waiting' => 'This match has not started yet. Please wait in the lobby.',
            'starting' => 'This match is starting. Please wait...',
            'finished' => 'This match has already ended.',
            default => 'Match is not available for this action.',
        };
    }
}