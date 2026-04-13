<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class LogGameActions
{
    /**
     * Handle an incoming request.
     *
     * Log important game actions for debugging and analytics
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Process request
        $response = $next($request);

        // Calculate response time
        $responseTime = (microtime(true) - $startTime) * 1000; // in ms

        // Log if response time is too high
        if ($responseTime > 100) {
            Log::warning('Slow game action', [
                'action' => $request->route()->getActionName(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'match_id' => $request->route('match')?->id,
                'response_time_ms' => round($responseTime, 2),
                'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
            ]);
        }

        // Log failed actions
        if ($response->getStatusCode() >= 400) {
            Log::info('Game action failed', [
                'action' => $request->route()->getActionName(),
                'user_id' => $request->user()?->id,
                'status_code' => $response->getStatusCode(),
                'input' => $request->except(['password', 'token'])
            ]);
        }

        return $response;
    }
}