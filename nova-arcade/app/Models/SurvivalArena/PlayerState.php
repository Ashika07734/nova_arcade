<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
use App\Models\SurvivalArena\MatchPlayer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class PlayerState extends Model
{
    protected $table = 'sa_player_states';
    
    public $timestamps = false;
    
    protected $fillable = [
        'match_id',
        'user_id',
        'is_bot',
        'bot_name',
        'bot_difficulty',
        'position',
        'rotation',
        'velocity',
        'health',
        'shield',
        'stamina',
        'inventory',
        'active_weapon_slot',
        'ammo_current',
        'ammo_reserve',
        'is_reloading',
        'is_shooting',
        'is_sprinting',
        'is_crouching',
        'last_updated'
    ];

    protected $casts = [
        'position' => 'array',
        'rotation' => 'array',
        'velocity' => 'array',
        'inventory' => 'array',
        'is_bot' => 'boolean',
        'is_reloading' => 'boolean',
        'is_shooting' => 'boolean',
        'is_sprinting' => 'boolean',
        'is_crouching' => 'boolean',
        'last_updated' => 'datetime'
    ];

    // ========== Relationships ==========
    
    public function match(): BelongsTo
    {
        return $this->belongsTo(ArenaMatch::class, 'match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== Methods ==========
    
    public function updatePosition(array $position, array $rotation, ?array $velocity = null): void
    {
        $this->update([
            'position' => $position,
            'rotation' => $rotation,
            'velocity' => $velocity ?? $this->velocity,
            'last_updated' => now()
        ]);

        // Broadcast movement
        broadcast(new \App\Events\SurvivalArena\Player\PlayerMoved(
            $this->match,
            $this->user_id,
            $position,
            $rotation,
            $velocity
        ))->toOthers();

        // Cache the state
        $this->cacheState();
    }

    public function takeDamage(int $amount, ?int $attackerId = null): void
    {
        $originalHealth = $this->health;

        if ($this->shield > 0) {
            $this->shield -= $amount;
            if ($this->shield < 0) {
                $this->health += $this->shield;
                $this->shield = 0;
            }
        } else {
            $this->health -= $amount;
        }

        $this->health = max(0, $this->health);

        // Update match player damage taken
        $matchPlayer = $this->getMatchPlayer();
        if ($matchPlayer) {
            $matchPlayer->takeDamage($amount);
        }

        // Broadcast damage event
        broadcast(new \App\Events\SurvivalArena\Player\PlayerDamaged(
            $this->match,
            $this->user_id,
            $amount,
            $this->health,
            $this->shield,
            $attackerId
        ))->toOthers();

        if ($this->health <= 0) {
            $this->die($attackerId);
        }

        $this->save();
    }

    protected function die(?int $killerId = null): void
    {
        $this->health = 0;

        $matchPlayer = $this->getMatchPlayer();
        if ($matchPlayer) {
            $matchPlayer->recordDeath($this->position);
        }

        if ($killerId) {
            // Increment killer's kill count even when victim is a bot.
            $killer = $this->match->players()
                ->where('user_id', $killerId)
                ->first();
            if ($killer) {
                $killer->incrementKills();
            }
        }

        if ($killerId && $this->user_id) {
            // Record kill
            $weapon = $this->getCurrentWeapon();

            if ($killerId > 0) {
                PlayerKill::create([
                    'match_id' => $this->match_id,
                    'killer_id' => $killerId,
                    'victim_id' => $this->user_id,
                    'weapon_id' => $weapon?->id,
                    'distance' => $this->calculateDistance($killerId),
                    'headshot' => false,
                    'kill_position' => $this->position
                ]);
            }

            // Broadcast kill event
            broadcast(new \App\Events\SurvivalArena\Player\PlayerKilled(
                $this->match,
                $killerId,
                $this->user_id,
                $weapon?->name ?? 'Unknown',
                false
            ))->toOthers();
        }

        // Check win condition
        $this->match->checkWinCondition();

        $this->save();
    }

    public function heal(int $amount): void
    {
        $this->health = min(100, $this->health + $amount);
        $this->save();
    }

    public function addShield(int $amount): void
    {
        $this->shield = min(100, $this->shield + $amount);
        $this->save();
    }

    public function reload(): void
    {
        $weapon = $this->getCurrentWeapon();
        
        if (!$weapon || $this->is_reloading) {
            return;
        }

        $neededAmmo = $weapon->magazine_size - $this->ammo_current;
        $ammoToReload = min($neededAmmo, $this->ammo_reserve);

        if ($ammoToReload <= 0) {
            return;
        }

        $this->is_reloading = true;
        $this->save();

        // Schedule reload completion
        Cache::put(
            "player:{$this->user_id}:reload_complete",
            now()->addSeconds($weapon->reload_time),
            now()->addSeconds($weapon->reload_time + 1)
        );
    }

    public function finishReload(): void
    {
        $weapon = $this->getCurrentWeapon();
        
        if (!$weapon) {
            $this->is_reloading = false;
            $this->save();
            return;
        }

        $neededAmmo = $weapon->magazine_size - $this->ammo_current;
        $ammoToReload = min($neededAmmo, $this->ammo_reserve);

        $this->ammo_current += $ammoToReload;
        $this->ammo_reserve -= $ammoToReload;
        $this->is_reloading = false;

        $this->save();

        Cache::forget("player:{$this->user_id}:reload_complete");
    }

    public function switchWeapon(int $slot): void
    {
        if ($slot < 0 || $slot > 2) {
            return;
        }

        $this->active_weapon_slot = $slot;
        $this->save();
    }

    public function pickupWeapon(Weapon $weapon): void
    {
        $inventory = $this->inventory;

        // Find empty slot or replace current weapon
        if (count($inventory) < 2) {
            $inventory[] = [
                'weapon_id' => $weapon->id,
                'ammo' => $weapon->magazine_size
            ];
        } else {
            $inventory[$this->active_weapon_slot] = [
                'weapon_id' => $weapon->id,
                'ammo' => $weapon->magazine_size
            ];
        }

        $this->inventory = $inventory;
        $this->save();
    }

    public function getCurrentWeapon(): ?Weapon
    {
        if (empty($this->inventory) || !isset($this->inventory[$this->active_weapon_slot])) {
            return null;
        }

        return Weapon::find($this->inventory[$this->active_weapon_slot]['weapon_id']);
    }

    public function isInsideSafeZone(): bool
    {
        $currentZone = $this->match->safeZones()
            ->where('ends_at', '>', now())
            ->latest()
            ->first();

        if (!$currentZone) {
            return true;
        }

        $distance = sqrt(
            pow($this->position['x'] - $currentZone->center['x'], 2) +
            pow($this->position['z'] - $currentZone->center['z'], 2)
        );

        return $distance <= $currentZone->radius;
    }

    protected function calculateDistance(int $otherPlayerId): float
    {
        $otherPlayer = PlayerState::where('match_id', $this->match_id)
            ->where('user_id', $otherPlayerId)
            ->first();

        if (!$otherPlayer) {
            return 0;
        }

        return sqrt(
            pow($this->position['x'] - $otherPlayer->position['x'], 2) +
            pow($this->position['y'] - $otherPlayer->position['y'], 2) +
            pow($this->position['z'] - $otherPlayer->position['z'], 2)
        );
    }

    protected function cacheState(): void
    {
        $stateKey = $this->user_id ?: ('bot:' . ($this->bot_name ?: $this->id));

        Cache::put(
            "player_state:{$this->match_id}:{$stateKey}",
            $this->toArray(),
            now()->addSeconds(10)
        );
    }

    private function getMatchPlayer(): ?MatchPlayer
    {
        if ($this->is_bot) {
            return $this->match->players()
                ->where('is_bot', true)
                ->where('bot_name', $this->bot_name)
                ->first();
        }

        if (!$this->user_id) {
            return null;
        }

        return $this->match->players()
            ->where('user_id', $this->user_id)
            ->first();
    }
}