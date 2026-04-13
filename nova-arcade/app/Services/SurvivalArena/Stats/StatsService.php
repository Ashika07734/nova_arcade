<?php

namespace App\Services\SurvivalArena\Stats;

use App\Models\User;
use App\Models\SurvivalArena\UserStats;
use App\Models\SurvivalArena\MatchPlayer;

class StatsService
{
    /**
     * Update user stats after match
     */
    public function updateUserStats(User $user, MatchPlayer $matchPlayer): void
    {
        $stats = $user->stats ?? $user->getOrCreateStats();
        
        // Increment totals
        $stats->increment('total_matches');
        $stats->increment('kills', $matchPlayer->kills);
        $stats->increment('deaths', $matchPlayer->deaths);
        $stats->increment('total_damage', $matchPlayer->damage_dealt);
        $stats->increment('headshots', $matchPlayer->headshots);
        
        // Update placement stats
        if ($matchPlayer->placement === 1) {
            $stats->increment('wins');
        }
        
        if ($matchPlayer->placement <= 5) {
            $stats->increment('top_5');
        }
        
        if ($matchPlayer->placement <= 10) {
            $stats->increment('top_10');
        }
        
        // Update K/D ratio
        $kd = $stats->deaths > 0 ? $stats->kills / $stats->deaths : $stats->kills;
        $stats->kd_ratio = round($kd, 2);
        
        // Update win rate
        $winRate = $stats->total_matches > 0 
            ? ($stats->wins / $stats->total_matches) * 100 
            : 0;
        $stats->win_rate = round($winRate, 2);
        
        // Update highest kills in match
        if ($matchPlayer->kills > $stats->highest_kills_match) {
            $stats->highest_kills_match = $matchPlayer->kills;
        }
        
        // Update playtime
        $stats->increment('total_playtime', $matchPlayer->survival_time);
        
        $stats->save();
    }
    
    /**
     * Get user statistics summary
     */
    public function getUserStatsSummary(User $user): array
    {
        $stats = $user->stats;
        
        if (!$stats) {
            return $this->getEmptyStats();
        }
        
        return [
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
            'headshot_percentage' => $this->calculateHeadshotPercentage($stats),
            'longest_kill' => $stats->longest_kill,
            'highest_kills_match' => $stats->highest_kills_match,
            'total_playtime' => $stats->formatted_playtime,
            'average_placement' => $this->calculateAveragePlacement($user),
            'average_kills' => $this->calculateAverageKills($stats),
            'average_damage' => $this->calculateAverageDamage($stats)
        ];
    }
    
    /**
     * Get empty stats
     */
    private function getEmptyStats(): array
    {
        return [
            'total_matches' => 0,
            'wins' => 0,
            'top_5' => 0,
            'top_10' => 0,
            'kills' => 0,
            'deaths' => 0,
            'kd_ratio' => '0.00',
            'win_rate' => '0.0%',
            'total_damage' => '0',
            'headshots' => 0,
            'headshot_percentage' => '0.0%',
            'longest_kill' => 0,
            'highest_kills_match' => 0,
            'total_playtime' => '0h 0m',
            'average_placement' => 0,
            'average_kills' => 0,
            'average_damage' => 0
        ];
    }
    
    /**
     * Calculate headshot percentage
     */
    private function calculateHeadshotPercentage(UserStats $stats): string
    {
        if ($stats->kills <= 0) {
            return '0.0%';
        }
        
        $percentage = ($stats->headshots / $stats->kills) * 100;
        return number_format($percentage, 1) . '%';
    }
    
    /**
     * Calculate average placement
     */
    private function calculateAveragePlacement(User $user): float
    {
        $avg = $user->matchPlayers()
            ->whereNotNull('placement')
            ->avg('placement');
        
        return $avg ? round($avg, 1) : 0;
    }
    
    /**
     * Calculate average kills per match
     */
    private function calculateAverageKills(UserStats $stats): float
    {
        if ($stats->total_matches <= 0) {
            return 0;
        }
        
        return round($stats->kills / $stats->total_matches, 1);
    }
    
    /**
     * Calculate average damage per match
     */
    private function calculateAverageDamage(UserStats $stats): int
    {
        if ($stats->total_matches <= 0) {
            return 0;
        }
        
        return (int) ($stats->total_damage / $stats->total_matches);
    }
    
    /**
     * Get weapon statistics for user
     */
    public function getWeaponStats(User $user): array
    {
        $kills = \DB::table('sa_player_kills')
            ->where('killer_id', $user->id)
            ->selectRaw('weapon_id, COUNT(*) as kill_count, AVG(distance) as avg_distance')
            ->groupBy('weapon_id')
            ->get();
        
        return $kills->map(function ($stat) {
            $weapon = \App\Models\SurvivalArena\Weapon::find($stat->weapon_id);
            
            return [
                'weapon_name' => $weapon?->name ?? 'Unknown',
                'kills' => $stat->kill_count,
                'average_distance' => round($stat->avg_distance, 1)
            ];
        })->toArray();
    }
}

