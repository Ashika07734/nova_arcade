<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SurvivalArena\ArenaMatch;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $stats = $user->stats ?? $user->getOrCreateStats();

        // Get daily missions
        $dailyMissions = $user->dailyMissions()
            ->today()
            ->get();

        // Get recent matches
        $recentMatches = $user->matchPlayers()
            ->with(['match', 'user'])
            ->latest()
            ->limit(5)
            ->get();

        // Get active matches count
        $activeMatches = ArenaMatch::active()->count();

        return view('dashboard', compact(
            'stats',
            'dailyMissions',
            'recentMatches',
            'activeMatches'
        ));
    }
}

