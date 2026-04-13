<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\SurvivalArena\MatchPlayer;
use App\Models\SurvivalArena\UserStats;
use App\Models\SurvivalArena\UserInventory;
use App\Models\SurvivalArena\DailyMission;
use App\Models\SurvivalArena\UserAchievement;
use App\Models\SurvivalArena\Leaderboard;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'avatar',
        'bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ========== Relationships ==========
    
    public function matchPlayers()
    {
        return $this->hasMany(MatchPlayer::class);
    }

    public function stats()
    {
        return $this->hasOne(UserStats::class);
    }

    public function inventory()
    {
        return $this->hasMany(UserInventory::class);
    }

    public function dailyMissions()
    {
        return $this->hasMany(DailyMission::class);
    }

    public function achievements()
    {
        return $this->hasMany(UserAchievement::class);
    }
    // Add to relationships section

    public function leaderboardEntries()
    {
        return $this->hasMany(Leaderboard::class);
    }

    // Add helper method
    public function getLeaderboardRank(
        string $period = 'all_time',
        string $category = 'wins',
        ?string $season = null
        ): ?int {
        return Leaderboard::getUserRank($this->id, $period, $category, $season);
    }

    // ========== Accessors ==========
    
    public function getAvatarUrlAttribute()
    {
        return $this->avatar 
            ? asset('storage/' . $this->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }

    // ========== Methods ==========
    
    public function getOrCreateStats()
    {
        return $this->stats()->firstOrCreate([
            'user_id' => $this->id
        ]);
    }
}
