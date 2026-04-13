<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\Leaderboard;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the landing/home page
     */
    public function index()
    {
        // Get some statistics for the homepage
        $totalPlayers = User::count();
        $activeMatches = ArenaMatch::active()->count();
        $onlinePlayers = ArenaMatch::active()->sum('current_players');
        $totalMatches = ArenaMatch::finished()->count();

        // Get top 3 players (Hall of Fame)
        $topPlayers = Leaderboard::getTopPlayers('all_time', 'wins', 3);

        // Get featured game modes
        $gameModes = [
            [
                'name' => 'Solo',
                'description' => 'Free-for-all battle royale. 50 players, one winner.',
                'icon' => 'SOLO',
                'players' => '1-50',
                'available' => true,
            ],
            [
                'name' => 'Duo',
                'description' => 'Team up with a friend. 25 teams of 2.',
                'icon' => 'DUO',
                'players' => '2-50',
                'available' => true,
            ],
            [
                'name' => 'Squad',
                'description' => 'Form a squad of 4. 12 teams compete.',
                'icon' => 'SQUAD',
                'players' => '4-48',
                'available' => true,
            ],
        ];

        // Recent match highlights (last 5 finished matches)
        $recentMatches = ArenaMatch::finished()
            ->with(['winner', 'players'])
            ->latest('ended_at')
            ->limit(5)
            ->get();

        return view('welcome', compact(
            'totalPlayers',
            'activeMatches',
            'onlinePlayers',
            'totalMatches',
            'topPlayers',
            'gameModes',
            'recentMatches'
        ));
    }

    /**
     * About page
     */
    public function about()
    {
        return view('pages.about');
    }

    /**
     * How to play guide
     */
    public function howToPlay()
    {
        $controls = [
            'Movement' => [
                ['key' => 'W', 'action' => 'Move Forward'],
                ['key' => 'S', 'action' => 'Move Backward'],
                ['key' => 'A', 'action' => 'Move Left'],
                ['key' => 'D', 'action' => 'Move Right'],
                ['key' => 'Space', 'action' => 'Jump'],
                ['key' => 'Shift', 'action' => 'Sprint'],
                ['key' => 'Ctrl', 'action' => 'Crouch'],
            ],
            'Combat' => [
                ['key' => 'Left Click', 'action' => 'Shoot'],
                ['key' => 'Right Click', 'action' => 'Aim Down Sights'],
                ['key' => 'R', 'action' => 'Reload'],
                ['key' => '1, 2, 3', 'action' => 'Switch Weapons'],
                ['key' => 'Q', 'action' => 'Use Throwable'],
            ],
            'Interaction' => [
                ['key' => 'E', 'action' => 'Pickup Item'],
                ['key' => 'Tab', 'action' => 'Inventory'],
                ['key' => 'M', 'action' => 'Map'],
                ['key' => 'Esc', 'action' => 'Menu'],
            ],
        ];

        $gameplayTips = [
            'The safe zone shrinks every 60 seconds. Stay inside or take damage!',
            'Headshots deal 2x damage. Aim carefully!',
            'Sprinting makes you faster but drains stamina.',
            'Crouching makes you harder to hit and quieter.',
            'Different weapons have different ranges and damage.',
            'Collect loot from buildings and defeated players.',
            'Use cover to avoid enemy fire.',
            'Listen for footsteps to detect nearby enemies.',
        ];

        return view('pages.how-to-play', compact('controls', 'gameplayTips'));
    }

    /**
     * FAQ page
     */
    public function faq()
    {
        $faqs = [
            [
                'question' => 'What is Survival Arena 3D?',
                'answer' => 'Survival Arena 3D is a browser-based multiplayer battle royale game where up to 50 players compete to be the last one standing in a shrinking arena.',
            ],
            [
                'question' => 'How do I play?',
                'answer' => 'Create an account, join matchmaking, and compete against other players. Use WASD to move, mouse to aim, and left-click to shoot.',
            ],
            [
                'question' => 'Is it free to play?',
                'answer' => 'Yes! Survival Arena 3D is completely free to play.',
            ],
            [
                'question' => 'What are the system requirements?',
                'answer' => 'You need a modern web browser (Chrome, Firefox, Edge) and a stable internet connection. A dedicated GPU is recommended for the best experience.',
            ],
            [
                'question' => 'Can I play with friends?',
                'answer' => 'Yes! You can create a custom match and share the match code with friends, or team up in Duo/Squad modes.',
            ],
            [
                'question' => 'How does the safe zone work?',
                'answer' => 'The safe zone shrinks every 60 seconds. Players outside the zone take continuous damage. Stay inside to survive!',
            ],
            [
                'question' => 'How do I level up?',
                'answer' => 'Earn XP by playing matches, getting kills, and achieving high placements. XP increases your level and unlocks rewards.',
            ],
            [
                'question' => 'Can I customize my character?',
                'answer' => 'Yes! Unlock skins, emotes, and other cosmetics as you level up and complete achievements.',
            ],
        ];

        return view('pages.faq', compact('faqs'));
    }

    /**
     * Game statistics page
     */
    public function stats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_matches' => ArenaMatch::count(),
            'matches_today' => ArenaMatch::whereDate('created_at', today())->count(),
            'active_matches' => ArenaMatch::active()->count(),
            'total_kills' => \DB::table('sa_player_kills')->count(),
            'average_match_duration' => $this->getAverageMatchDuration(),
            'most_used_weapon' => $this->getMostUsedWeapon(),
            'longest_kill' => $this->getLongestKill(),
        ];

        return view('pages.stats', compact('stats'));
    }

    /**
     * Helper: Get average match duration
     */
    private function getAverageMatchDuration()
    {
        $matches = ArenaMatch::finished()
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->get(['started_at', 'ended_at']);

        if ($matches->isNotEmpty()) {
            $avgDuration = (int) round($matches->avg(function ($match) {
                return $match->ended_at->diffInSeconds($match->started_at);
            }));

            $minutes = floor($avgDuration / 60);
            $seconds = $avgDuration % 60;

            return sprintf('%02d:%02d', $minutes, $seconds);
        }

        return 'N/A';
    }

    /**
     * Helper: Get most used weapon
     */
    private function getMostUsedWeapon()
    {
        $weapon = \DB::table('sa_player_kills')
            ->select('weapon_id', \DB::raw('COUNT(*) as kill_count'))
            ->whereNotNull('weapon_id')
            ->groupBy('weapon_id')
            ->orderByDesc('kill_count')
            ->first();

        if ($weapon) {
            $weaponModel = \App\Models\SurvivalArena\Weapon::find($weapon->weapon_id);

            return $weaponModel?->name ?? 'Unknown';
        }

        return 'N/A';
    }

    /**
     * Helper: Get longest kill distance
     */
    private function getLongestKill()
    {
        $kill = \DB::table('sa_player_kills')
            ->orderByDesc('distance')
            ->first();

        return $kill ? number_format($kill->distance, 1) . 'm' : 'N/A';
    }

    /**
     * Privacy policy
     */
    public function privacy()
    {
        return view('pages.privacy');
    }

    /**
     * Terms of service
     */
    public function terms()
    {
        return view('pages.terms');
    }

    /**
     * Contact page
     */
    public function contact()
    {
        return view('pages.contact');
    }

    /**
     * Submit contact form
     */
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // Send email to admin (implement mail sending)
        // Mail::to('admin@survival-arena.com')->send(new ContactFormMail($validated));

        return back()->with('success', 'Thank you for contacting us! We\'ll get back to you soon.');
    }
}
