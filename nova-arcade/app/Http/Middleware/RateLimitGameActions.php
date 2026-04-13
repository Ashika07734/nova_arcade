<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitGameActions
{
    public function handle(Request $request, Closure $next, string $action = 'default'): Response
    {
        $userId = $request->user()->id;
        $matchId = $request->route('match')?->id ?? 'global';
        $key = "game-action:{$userId}:{$matchId}:{$action}";

        $limits = [
            'position' => ['max' => 60, 'decay' => 1],
            'shoot' => ['max' => 10, 'decay' => 1],
            'pickup' => ['max' => 5, 'decay' => 1],
            'reload' => ['max' => 2, 'decay' => 1],
            'switch' => ['max' => 5, 'decay' => 1],
            'default' => ['max' => 30, 'decay' => 1],
        ];

        $limit = $limits[$action] ?? $limits['default'];

        $executed = RateLimiter::attempt(
            $key,
            $limit['max'],
            fn () => true,
            $limit['decay']
        );

        if (!$executed) {
            return response()->json([
                'error' => 'Too many requests',
                'message' => "Rate limit exceeded for action: {$action}",
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('X-RateLimit-Limit', $limit['max']);
            $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $limit['max']));
            $response->headers->set('Retry-After', RateLimiter::availableIn($key));
        }

        return $response;
    }
}

