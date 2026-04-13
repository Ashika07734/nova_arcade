<?php

namespace App\Services\SurvivalArena\Stats;

use App\Models\SurvivalArena\Leaderboard;
use App\Models\SurvivalArena\UserStats;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * Update all leaderboards
     */
    public function updateAllLeaderboards(): void
    {
        $periods = ['all_time', 'weekly', 'monthly'];
        $categories = ['wins', 'kills', 'kd_ratio', 'damage'];
        
        foreach ($periods as $period) {
            foreach ($categories as $category) {
                $this->updateLeaderboard($period, $category);
            }
        }
    }
    
    /**
     * Update specific leaderboard
     */
    public function updateLeaderboard(string $period, string $category): void
    {
        // Get scores
        $scores = $this->getScoresForCategory($category, $period);
        
        // Clear existing rankings
        Leaderboard::where('period', $period)
            ->where('category', $category)
            ->delete();
        
        // Insert new rankings
        $rank = 1;
        foreach ($scores as $score) {
            Leaderboard::create([
                'user_id' => $score->user_id,
                'period' => $period,
                'category' => $category,
                'rank' => $rank++,
                'score' => $score->score,
                'updated_at' => now()
            ]);
        }
    }
    
    /**
     * Get scores for category
     */
    private function getScoresForCategory(string $category, string $period)
    {
        $query = DB::table('sa_user_stats')
            ->join('users', 'sa_user_stats.user_id', '=', 'users.id')
            ->select('sa_user_stats.user_id', DB::raw("$category as score"))
            ->where('sa_user_stats.' . $category, '>', 0)
            ->orderByDesc('score')
            ->limit(1000);
        
        // Apply period filters for weekly/monthly
        // Note: This would require additional tracking tables
        // For now, use all-time data
        
        return $query->get();
    }
    
    /**
     * Get user's rank across all categories
     */
    public function getUserRanks(int $userId): array
    {
        $categories = ['wins', 'kills', 'kd_ratio', 'damage'];
        $ranks = [];
        
        foreach ($categories as $category) {
            $rank = Leaderboard::getUserRank($userId, 'all_time', $category);
            $ranks[$category] = $rank ?? 'Unranked';
        }
        
        return $ranks;
    }
    
    /**
     * Get top players for multiple categories
     */
    public function getTopPlayersMultiCategory(int $limit = 10): array
    {
        return [
            'wins' => Leaderboard::getTopPlayers('all_time', 'wins', $limit),
            'kills' => Leaderboard::getTopPlayers('all_time', 'kills', $limit),
            'kd_ratio' => Leaderboard::getTopPlayers('all_time', 'kd_ratio', $limit),
            'damage' => Leaderboard::getTopPlayers('all_time', 'damage', $limit),
        ];
    }
}

