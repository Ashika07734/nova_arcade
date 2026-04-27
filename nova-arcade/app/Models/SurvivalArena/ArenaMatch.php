<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
use App\Models\SurvivalArena\MatchPlayer;
use App\Models\SurvivalArena\Leaderboard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ArenaMatch extends Model
{
    protected $table = 'sa_matches';

    protected $fillable = [
        'match_code',
        'mode',
        'game_mode',
        'difficulty',
        'bot_count',
        'max_players',
        'current_players',
        'status',
        'map_data',
        'winner_id',
        'started_at',
        'ended_at'
    ];

    protected $casts = [
        'map_data' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'bot_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($match) {
            if (empty($match->match_code)) {
                $match->match_code = strtoupper(Str::random(6));
            }
        });
    }

    public function players(): HasMany
    {
        return $this->hasMany(MatchPlayer::class, 'match_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function playerStates(): HasMany
    {
        return $this->hasMany(PlayerState::class, 'match_id');
    }

    public function lootSpawns(): HasMany
    {
        return $this->hasMany(LootSpawn::class, 'match_id');
    }

    public function safeZones(): HasMany
    {
        return $this->hasMany(SafeZone::class, 'match_id')->orderBy('phase');
    }

    public function kills(): HasMany
    {
        return $this->hasMany(PlayerKill::class, 'match_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['waiting', 'starting', 'in_progress']);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }

    public function canJoin(): bool
    {
        return $this->status === 'waiting'
            && $this->current_players < $this->max_players;
    }

    public function addPlayer(User $user): self
    {
        if (!$this->canJoin()) {
            throw new \Exception('Match is full or already started');
        }

        $this->players()->create([
            'user_id' => $user->id,
            'joined_at' => now()
        ]);

        $this->increment('current_players');

        $this->safeBroadcast(new \App\Events\SurvivalArena\Match\PlayerJoinedMatch($this, $user));

        return $this;
    }

    public function addBot(string $botName, string $difficulty = 'easy'): MatchPlayer
    {
        $botPlayer = $this->players()->create([
            'user_id' => null,
            'is_bot' => true,
            'bot_name' => $botName,
            'bot_difficulty' => $difficulty,
            'joined_at' => now(),
        ]);

        $this->increment('current_players');

        return $botPlayer;
    }

    public function removePlayer(User $user): self
    {
        $this->players()->where('user_id', $user->id)->delete();
        $this->decrement('current_players');

        $this->safeBroadcast(new \App\Events\SurvivalArena\Match\PlayerLeftMatch($this, $user->id));

        return $this;
    }

    public function start(): self
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now()
        ]);

        foreach ($this->players()->get() as $player) {
            $this->initializePlayerState($player);
        }

        $this->createSafeZone(1);

        $this->safeBroadcast(new \App\Events\SurvivalArena\Match\MatchStarted($this));

        return $this;
    }

    public function end(?User $winner = null): self
    {
        $this->update([
            'status' => 'finished',
            'winner_id' => $winner?->id,
            'ended_at' => now()
        ]);

        $this->calculatePlacements();

        if (($this->mode ?? $this->game_mode) === 'solo') {
            $this->calculateSoloScores();
        }

        $this->updateUserStats();
        $this->refreshLeaderboards();

        $this->safeBroadcast(new \App\Events\SurvivalArena\Match\MatchEnded($this));

        return $this;
    }

    public function getAlivePlayers(): int
    {
        return $this->players()->where('is_alive', true)->count();
    }

    public function getAliveHumans(): int
    {
        return $this->players()
            ->where('is_alive', true)
            ->where('is_bot', false)
            ->count();
    }

    public function getAliveBots(): int
    {
        return $this->players()
            ->where('is_alive', true)
            ->where('is_bot', true)
            ->count();
    }

    public function checkWinCondition(): bool
    {
        if (($this->mode ?? $this->game_mode) === 'solo') {
            if ($this->getAliveHumans() <= 0) {
                $this->end(null);
                return true;
            }

            if ($this->getAliveBots() <= 0 && $this->getAliveHumans() >= 1) {
                $winnerPlayer = $this->players()
                    ->where('is_bot', false)
                    ->where('is_alive', true)
                    ->first();

                $this->end($winnerPlayer?->user);
                return true;
            }

            return false;
        }

        $alive = $this->getAlivePlayers();

        if ($alive <= 1) {
            $winner = $this->players()
                ->where('is_alive', true)
                ->first();

            $this->end($winner?->user);

            return true;
        }

        return false;
    }

    protected function initializePlayerState(MatchPlayer $player): void
    {
        $spawnPosition = $this->getSpawnPositionForPlayer($player);

        PlayerState::create([
            'match_id' => $this->id,
            'user_id' => $player->user_id,
            'is_bot' => $player->is_bot,
            'bot_name' => $player->bot_name,
            'bot_difficulty' => $player->bot_difficulty,
            'position' => $spawnPosition,
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'velocity' => ['x' => 0, 'y' => 0, 'z' => 0],
            'health' => 100,
            'shield' => 0,
            'stamina' => 100,
            'inventory' => [],
            'ammo_current' => $player->is_bot ? 999 : 30,
            'ammo_reserve' => $player->is_bot ? 9999 : 120,
            'last_updated' => now()
        ]);
    }

    protected function getSpawnPositionForPlayer(MatchPlayer $player): array
    {
        $spawnPoints = $this->map_data['spawn_points'] ?? [];

        if ($player->is_bot) {
            $botSpawns = $spawnPoints['bots'] ?? [];
            $botIndex = $this->players()
                ->where('is_bot', true)
                ->where('id', '<=', $player->id)
                ->count() - 1;

            if (isset($botSpawns[$botIndex])) {
                return [
                    'x' => $botSpawns[$botIndex]['x'],
                    'y' => $botSpawns[$botIndex]['y'] ?? 1,
                    'z' => $botSpawns[$botIndex]['z'],
                ];
            }
        }

        if (isset($spawnPoints['player'])) {
            return [
                'x' => $spawnPoints['player']['x'],
                'y' => $spawnPoints['player']['y'] ?? 1,
                'z' => $spawnPoints['player']['z'],
            ];
        }

        return $this->getRandomSpawnPosition();
    }

    protected function getRandomSpawnPosition(): array
    {
        $roads = $this->map_data['road_lanes'] ?? [];

        if (!empty($roads)) {
            $point = $roads[array_rand($roads)];

            return [
                'x' => $point['x'] + rand(-6, 6),
                'y' => 1,
                'z' => $point['z'] + rand(-6, 6),
            ];
        }

        $radius = 80;
        $angle = rand(0, 360) * (M_PI / 180);
        $distance = rand(0, $radius);

        return [
            'x' => cos($angle) * $distance,
            'y' => 1,
            'z' => sin($angle) * $distance
        ];
    }

    protected function createSafeZone(int $phase): void
    {
        $config = config('games.survival-arena.safe_zone');
        $phaseConfig = $config['phases'][$phase] ?? $config['phases'][1];

        $radius = $config['initial_radius'] - (($phase - 1) * $config['shrink_amount']);
        $radius = max($radius, $config['min_radius']);

        SafeZone::create([
            'match_id' => $this->id,
            'phase' => $phase,
            'center' => ['x' => 0, 'z' => 0],
            'radius' => $radius,
            'damage_per_second' => $phaseConfig['damage'],
            'starts_at' => now(),
            'ends_at' => now()->addSeconds($phaseConfig['duration'])
        ]);
    }

    protected function calculatePlacements(): void
    {
        $players = $this->players()
            ->orderBy('is_alive', 'desc')
            ->orderBy('died_at', 'desc')
            ->get();

        foreach ($players as $index => $player) {
            $player->update([
                'placement' => $index + 1
            ]);
        }
    }

    protected function calculateSoloScores(): void
    {
        $players = $this->players()->where('is_bot', false)->get();

        foreach ($players as $player) {
            $state = $this->playerStates()->where('user_id', $player->user_id)->first();

            $accuracyBonus = $player->shots_fired > 0
                ? (int) round(($player->shots_hit / $player->shots_fired) * 20)
                : 0;

            $survivalBonus = (int) floor(($player->survival_time ?? 0) / 10);
            $healthBonus = (int) max(0, $state?->health ?? 0);
            $victoryBonus = $player->placement === 1 ? 100 : 0;

            $score =
                ($player->kills * 10) +
                ($player->headshots * 20) +
                $accuracyBonus +
                $survivalBonus +
                $healthBonus +
                $victoryBonus;

            $player->update(['score' => $score]);
        }
    }

    protected function updateUserStats(): void
    {
        foreach ($this->players as $player) {
            if ($player->is_bot || !$player->user) {
                continue;
            }

            $stats = $player->user->getOrCreateStats();
            $stats->update([
                'total_matches' => $stats->total_matches + 1,
                'wins' => $stats->wins + ($player->placement === 1 ? 1 : 0),
                'top_5' => $stats->top_5 + ($player->placement <= 5 ? 1 : 0),
                'top_10' => $stats->top_10 + ($player->placement <= 10 ? 1 : 0),
                'kills' => $stats->kills + $player->kills,
                'deaths' => $stats->deaths + $player->deaths,
                'total_damage' => $stats->total_damage + $player->damage_dealt,
                'headshots' => $stats->headshots + $player->headshots,
                'longest_kill' => max($stats->longest_kill, $player->longest_kill ?? 0),
                'highest_kills_match' => max($stats->highest_kills_match, $player->kills),
                'total_playtime' => $stats->total_playtime + $player->survival_time
            ]);

            $stats->updateKdRatio();
            $stats->updateWinRate();
        }
    }

    protected function refreshLeaderboards(): void
    {
        try {
            foreach (['wins', 'kills', 'damage', 'kd_ratio'] as $category) {
                Leaderboard::updateRankings('all_time', $category);
            }
        } catch (\Throwable $e) {
            Log::warning('Leaderboard refresh failed after match end', [
                'match_id' => $this->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function safeBroadcast(object $event): void
    {
        try {
            broadcast($event)->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Broadcast failed, continuing match flow', [
                'match_id' => $this->id,
                'event' => get_class($event),
                'message' => $e->getMessage(),
            ]);
        }
    }
}