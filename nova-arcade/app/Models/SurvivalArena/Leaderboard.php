<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Leaderboard extends Model
{
    protected $table = 'sa_leaderboards';
    
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'period',
        'category',
        'rank',
        'score',
        'season',
        'updated_at'
    ];

    protected $casts = [
        'updated_at' => 'datetime'
    ];

    // ========== Relationships ==========
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Scopes ==========
    
    public function scopePeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSeason($query, ?string $season = null)
    {
        if ($season) {
            return $query->where('season', $season);
        }
        
        return $query->whereNull('season');
    }

    public function scopeTop($query, int $limit = 100)
    {
        return $query->orderBy('rank')->limit($limit);
    }

    // ========== Static Methods ==========
    
    /**
     * Update leaderboards for a specific period and category
     */
    public static function updateRankings(string $period, string $category, ?string $season = null): void
    {
        // Get scores based on category
        $scores = static::getScoresForCategory($category, $period, $season);

        // Clear existing rankings
        static::where('period', $period)
            ->where('category', $category)
            ->when($season, fn($q) => $q->where('season', $season))
            ->delete();

        // Insert new rankings
        $rank = 1;
        foreach ($scores as $score) {
            static::create([
                'user_id' => $score->user_id,
                'period' => $period,
                'category' => $category,
                'rank' => $rank++,
                'score' => $score->score,
                'season' => $season,
                'updated_at' => now()
            ]);
        }

        // Clear cache
        static::clearCache($period, $category, $season);
    }

    /**
     * Get top players for display
     */
    public static function getTopPlayers(
        string $period = 'all_time',
        string $category = 'wins',
        int $limit = 100,
        ?string $season = null
    ) {
        $cacheKey = "leaderboard:{$period}:{$category}:" . ($season ?? 'current') . ":{$limit}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($period, $category, $limit, $season) {
            return static::with(['user:id,name,username,avatar'])
                ->period($period)
                ->category($category)
                ->season($season)
                ->top($limit)
                ->get();
        });
    }

    /**
     * Get user's rank in a leaderboard
     */
    public static function getUserRank(
        int $userId,
        string $period = 'all_time',
        string $category = 'wins',
        ?string $season = null
    ): ?int {
        $entry = static::where('user_id', $userId)
            ->period($period)
            ->category($category)
            ->season($season)
            ->first();

        return $entry?->rank;
    }

    /**
     * Get user's leaderboard entry
     */
    public static function getUserEntry(
        int $userId,
        string $period = 'all_time',
        string $category = 'wins',
        ?string $season = null
    ): ?self {
        return static::with('user')
            ->where('user_id', $userId)
            ->period($period)
            ->category($category)
            ->season($season)
            ->first();
    }

    /**
     * Get players around a specific rank (for context)
     */
    public static function getPlayersAround(
        int $rank,
        string $period = 'all_time',
        string $category = 'wins',
        int $range = 5,
        ?string $season = null
    ) {
        $minRank = max(1, $rank - $range);
        $maxRank = $rank + $range;

        return static::with(['user:id,name,username,avatar'])
            ->period($period)
            ->category($category)
            ->season($season)
            ->whereBetween('rank', [$minRank, $maxRank])
            ->orderBy('rank')
            ->get();
    }

    // ========== Helper Methods ==========
    
    /**
     * Get scores based on category
     */
    protected static function getScoresForCategory(
        string $category,
        string $period,
        ?string $season = null
    ) {
        $scoreExpression = match ($category) {
            'wins' => 'wins',
            'kills' => 'kills',
            'kd_ratio' => '(kd_ratio * 100)',
            'damage' => 'total_damage',
            default => 'wins',
        };

        $query = DB::table('sa_user_stats')
            ->select('user_id', DB::raw("{$scoreExpression} as score"))
            ->orderByDesc('score');

        // Apply period filters
        if ($period === 'weekly') {
            // This would need additional tracking in user_stats
            // For now, use all-time data
        } elseif ($period === 'monthly') {
            // This would need additional tracking in user_stats
            // For now, use all-time data
        }

        return $query->get();
    }

    /**
     * Clear cached leaderboard data
     */
    protected static function clearCache(string $period, string $category, ?string $season = null): void
    {
        $seasonKey = $season ?? 'current';
        
        // Clear different limit caches
        foreach ([10, 50, 100] as $limit) {
            Cache::forget("leaderboard:{$period}:{$category}:{$seasonKey}:{$limit}");
        }
    }

    // ========== Accessors ==========
    
    public function getRankWithSuffixAttribute(): string
    {
        $suffix = match($this->rank % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th'
        };

        // Handle 11th, 12th, 13th
        if (in_array($this->rank % 100, [11, 12, 13])) {
            $suffix = 'th';
        }

        return $this->rank . $suffix;
    }

    public function getMedalAttribute(): ?string
    {
        return match($this->rank) {
            1 => 'Gold',
            2 => 'Silver',
            3 => 'Bronze',
            default => null
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'wins' => 'Wins',
            'kills' => 'Total Kills',
            'kd_ratio' => 'K/D Ratio',
            'damage' => 'Total Damage',
            default => ucfirst($this->category)
        };
    }

    public function getPeriodLabelAttribute(): string
    {
        return match($this->period) {
            'all_time' => 'All Time',
            'weekly' => 'This Week',
            'monthly' => 'This Month',
            'seasonal' => 'Season ' . $this->season,
            default => ucfirst($this->period)
        };
    }

    // ========== Methods ==========
    
    public function toArray()
    {
        return [
            'user_id' => $this->user_id,
            'username' => $this->user->username ?? 'Unknown',
            'name' => $this->user->name ?? 'Unknown',
            'avatar_url' => $this->user->avatar_url ?? null,
            'rank' => $this->rank,
            'rank_with_suffix' => $this->rank_with_suffix,
            'medal' => $this->medal,
            'score' => $this->score,
            'category' => $this->category,
            'category_label' => $this->category_label,
            'period' => $this->period,
            'period_label' => $this->period_label,
            'season' => $this->season,
            'updated_at' => $this->updated_at?->toDateTimeString()
        ];
    }

    public function toGameData(): array
    {
        return [
            'userId' => $this->user_id,
            'username' => $this->user->username ?? 'Unknown',
            'avatarUrl' => $this->user->avatar_url ?? null,
            'rank' => $this->rank,
            'rankWithSuffix' => $this->rank_with_suffix,
            'medal' => $this->medal,
            'score' => $this->score,
            'category' => $this->category,
            'categoryLabel' => $this->category_label
        ];
    }
}