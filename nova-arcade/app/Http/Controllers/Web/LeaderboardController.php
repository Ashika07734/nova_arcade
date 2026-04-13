<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SurvivalArena\Leaderboard;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'all_time');
        $category = $request->get('category', 'wins');

        if (!in_array($period, ['all_time', 'weekly', 'monthly', 'seasonal'], true)) {
            $period = 'all_time';
        }

        if (!in_array($category, ['wins', 'kills', 'kd_ratio', 'damage'], true)) {
            $category = 'wins';
        }

        $topPlayers = Leaderboard::getTopPlayers($period, $category, 100);

        $userRank = null;
        $userEntry = null;
        $nearbyPlayers = null;

        if (Auth::check()) {
            $userRank = Leaderboard::getUserRank(Auth::id(), $period, $category);
            $userEntry = Leaderboard::getUserEntry(Auth::id(), $period, $category);

            if ($userRank) {
                $nearbyPlayers = Leaderboard::getPlayersAround($userRank, $period, $category, 5);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'topPlayers' => $topPlayers->map(fn ($entry) => $entry->toArray()),
                'userRank' => $userRank,
                'userEntry' => $userEntry?->toArray(),
                'nearbyPlayers' => $nearbyPlayers?->map(fn ($entry) => $entry->toArray()),
                'period' => $period,
                'category' => $category,
            ]);
        }

        return view('survival-arena.leaderboards', compact(
            'topPlayers',
            'userRank',
            'userEntry',
            'nearbyPlayers',
            'period',
            'category'
        ));
    }

    public function show(string $period, string $category, Request $request)
    {
        $request->merge([
            'period' => $period,
            'category' => $category,
        ]);

        return $this->index($request);
    }

    public function userRank(User $user, Request $request)
    {
        $period = $request->get('period', 'all_time');
        $category = $request->get('category', 'wins');

        return response()->json([
            'user_id' => $user->id,
            'rank' => Leaderboard::getUserRank($user->id, $period, $category),
            'entry' => Leaderboard::getUserEntry($user->id, $period, $category)?->toArray(),
        ]);
    }

    public function getData(Request $request)
    {
        $period = $request->get('period', 'all_time');
        $category = $request->get('category', 'wins');
        $limit = $request->get('limit', 100);

        $topPlayers = Leaderboard::getTopPlayers($period, $category, $limit);

        return response()->json([
            'players' => $topPlayers->map(fn ($entry) => $entry->toArray()),
        ]);
    }
}

