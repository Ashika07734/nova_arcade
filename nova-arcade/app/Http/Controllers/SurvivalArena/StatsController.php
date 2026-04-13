<?php

namespace App\Http\Controllers\SurvivalArena;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SurvivalArena\UserStats;
use App\Models\SurvivalArena\Leaderboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatsController extends Controller
{
    /**
     * Show user statistics
     */
    public function show(User $user)
    {
        $stats = $user->stats ?? $user->getOrCreateStats();

        $recentMatches = $user->matchPlayers()
            ->with('match')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url
            ],
            'stats' => [
                'total_matches' => $stats->total_matches,
                'wins' => $stats->wins,
                'top_5' => $stats->top_5,
                'top_10' => $stats->top_10,
                'kills' => $stats->kills,
                'deaths' => $stats->deaths,
                'kd_ratio' => $stats->formatted_kd_ratio,
                'win_rate' => $stats->formatted_win_rate,
                'total_damage' => number_format($stats->total_damage),
                'headshots' => $stats->headshots,
                'longest_kill' => $stats->longest_kill . 'm',
                'highest_kills_match' => $stats->highest_kills_match,
                'total_playtime' => $stats->formatted_playtime
            ],
            'recent_matches' => $recentMatches->map(function ($mp) {
                return [
                    'match_id' => $mp->match_id,
                    'placement' => $mp->placement,
                    'kills' => $mp->kills,
                    'survival_time' => $mp->formatted_survival_time,
                    'played_at' => $mp->created_at->diffForHumans()
                ];
            })
        ]);
    }

    /**
     * Get leaderboard
     */
    public function leaderboard(string $period, string $category)
    {
        if (!in_array($period, ['all_time', 'weekly', 'monthly', 'seasonal'])) {
            return response()->json(['error' => 'Invalid period'], 400);
        }

        if (!in_array($category, ['wins', 'kills', 'kd_ratio', 'damage'])) {
            return response()->json(['error' => 'Invalid category'], 400);
        }

        $topPlayers = Leaderboard::getTopPlayers($period, $category, 100);

        $userRank = null;
        if (Auth::check()) {
            $userRank = Leaderboard::getUserRank(Auth::id(), $period, $category);
        }

        return response()->json([
            'period' => $period,
            'category' => $category,
            'leaderboard' => $topPlayers->map(fn($entry) => $entry->toGameData()),
            'user_rank' => $userRank
        ]);
    }

    /**
     * Get current user's stats
     */
    public function me()
    {
        return $this->show(Auth::user());
    }

    /**
     * Get dashboard stats
     */
    public function dashboard()
    {
        $user = Auth::user();
        $stats = $user->stats ?? $user->getOrCreateStats();

        // Daily missions
        $dailyMissions = $user->dailyMissions()
            ->today()
            ->get()
            ->map(function ($mission) {
                return [
                    'description' => $mission->description,
                    'progress' => $mission->progress,
                    'target' => $mission->target,
                    'progress_percentage' => $mission->progress_percentage,
                    'completed' => $mission->completed,
                    'reward_xp' => $mission->reward_xp
                ];
            });

        // Recent matches
        $recentMatches = $user->matchPlayers()
            ->with('match')
            ->latest()
            ->limit(5)
            ->get();

        // Friends online (would need friend system)
        $friendsOnline = [];

        return response()->json([
            'stats' => [
                'total_matches' => $stats->total_matches,
                'wins' => $stats->wins,
                'kd_ratio' => $stats->formatted_kd_ratio,
                'win_rate' => $stats->formatted_win_rate
            ],
            'daily_missions' => $dailyMissions,
            'recent_matches' => $recentMatches,
            'friends_online' => $friendsOnline
        ]);
    }
}

