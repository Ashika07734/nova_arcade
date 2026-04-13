<?php

namespace App\Http\Controllers\SurvivalArena;

use App\Http\Controllers\Controller;
use App\Models\SurvivalArena\ArenaMatch;
use App\Services\SurvivalArena\Match\MatchmakingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchController extends Controller
{
    public function __construct(
        private MatchmakingService $matchmakingService
    ) {}

    /**
     * Show game landing page
     */
    public function landing()
    {
        $activeMatches = ArenaMatch::active()->count();
        $totalPlayers = ArenaMatch::active()->sum('current_players');
        
        return view('survival-arena.landing', compact('activeMatches', 'totalPlayers'));
    }

    /**
     * Show matchmaking screen
     */
    public function matchmaking()
    {
        return view('survival-arena.matchmaking');
    }

    /**
     * Join matchmaking queue
     */
    public function joinQueue(Request $request)
    {
        $request->validate([
            'game_mode' => 'required|in:solo,duo,squad'
        ]);

        try {
            $match = $this->matchmakingService->findOrCreateMatch(
                Auth::user(),
                $request->game_mode
            );

            return response()->json([
                'success' => true,
                'match' => [
                    'id' => $match->id,
                    'match_code' => $match->match_code,
                    'current_players' => $match->current_players,
                    'max_players' => $match->max_players,
                    'status' => $match->status
                ],
                'redirect' => $match->status === 'in_progress'
                    ? route('survival-arena.play', $match)
                    : route('survival-arena.lobby', $match)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Leave matchmaking queue
     */
    public function leaveQueue(Request $request)
    {
        // Implementation would remove user from waiting matches
        return response()->json(['success' => true]);
    }

    /**
     * Show create match form
     */
    public function create()
    {
        return view('survival-arena.matches.create');
    }

    /**
     * Store new match
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_name' => 'nullable|string|max:50',
            'game_mode' => 'required|in:solo,duo,squad',
            'max_players' => 'required|integer|min:2|max:50',
            'is_public' => 'required|boolean'
        ]);

        $match = ArenaMatch::create([
            'game_mode' => $validated['game_mode'],
            'max_players' => $validated['max_players'],
            'status' => 'waiting'
        ]);

        $match->addPlayer(Auth::user());

        return redirect()
            ->route('survival-arena.lobby', $match)
            ->with('success', 'Match created! Share code: ' . $match->match_code);
    }

    /**
     * Join existing match
     */
    public function join(ArenaMatch $match)
    {
        if (!$match->canJoin()) {
            return redirect()
                ->route('survival-arena.matchmaking')
                ->with('error', 'Match is full or already started');
        }

        try {
            $match->addPlayer(Auth::user());

            return redirect()
                ->route('survival-arena.lobby', $match)
                ->with('success', 'Joined match!');

        } catch (\Exception $e) {
            return redirect()
                ->route('survival-arena.matchmaking')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show game play screen
     */
    public function play(ArenaMatch $match)
    {
        // Check if user is in this match
        $isPlayer = $match->players()
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isPlayer) {
            abort(403, 'You are not in this match');
        }

        if ($match->status !== 'in_progress') {
            return redirect()
                ->route('survival-arena.lobby', $match)
                ->with('error', 'Match has not started yet');
        }

        // Get player state
        $playerState = $match->playerStates()
            ->where('user_id', Auth::id())
            ->first();

        return view('survival-arena.game', compact('match', 'playerState'));
    }

    /**
     * Show match results
     */
    public function results(ArenaMatch $match)
    {
        if ($match->status !== 'finished') {
            abort(404, 'Match is not finished');
        }

        $players = $match->players()
            ->with('user')
            ->orderBy('placement')
            ->get();

        $currentPlayer = $players->firstWhere('user_id', Auth::id());

        return view('survival-arena.results', compact('match', 'players', 'currentPlayer'));
    }

    /**
     * Browse available matches
     */
    public function browse(Request $request)
    {
        $matches = ArenaMatch::waiting()
            ->where('current_players', '>', 0)
            ->orderBy('current_players', 'desc')
            ->paginate(12);

        return view('survival-arena.matches.browse', compact('matches'));
    }

    /**
     * Join match by code
     */
    public function joinByCode(Request $request)
    {
        $request->validate([
            'match_code' => 'required|string|size:6'
        ]);

        $match = ArenaMatch::where('match_code', strtoupper($request->match_code))->first();

        if (!$match) {
            return back()->with('error', 'Match not found');
        }

        return $this->join($match);
    }
}