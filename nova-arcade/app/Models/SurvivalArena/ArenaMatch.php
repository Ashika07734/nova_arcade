<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
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
        'game_mode',
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
        'ended_at' => 'datetime'
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

        foreach ($this->players as $player) {
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
        $this->updateUserStats();

        $this->safeBroadcast(new \App\Events\SurvivalArena\Match\MatchEnded($this));

        return $this;
    }

    public function getAlivePlayers(): int
    {
        return $this->players()->where('is_alive', true)->count();
    }

    public function checkWinCondition(): bool
    {
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
        $spawnPosition = $this->getRandomSpawnPosition();

        PlayerState::create([
            'match_id' => $this->id,
            'user_id' => $player->user_id,
            'position' => $spawnPosition,
            'rotation' => ['x' => 0, 'y' => 0, 'z' => 0],
            'velocity' => ['x' => 0, 'y' => 0, 'z' => 0],
            'health' => 100,
            'shield' => 0,
            'stamina' => 100,
            'inventory' => [],
            'last_updated' => now()
        ]);
    }

    protected function getRandomSpawnPosition(): array
    {
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

        $this->updateUserStats();
    }

    protected function updateUserStats(): void
    {
        foreach ($this->players as $player) {
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