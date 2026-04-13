<?php

namespace App\Services\SurvivalArena\Match;

use App\Events\SurvivalArena\SafeZone\SafeZoneUpdated;
use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\PlayerState;
use App\Models\SurvivalArena\SafeZone;
use Illuminate\Support\Collection;

class SafeZoneService
{
    public function updateSafeZone(ArenaMatch $match): ?SafeZone
    {
        $currentZone = $match->safeZones()->latest('phase')->first();

        if (!$currentZone || $currentZone->ends_at->isFuture()) {
            return null;
        }

        $nextPhase = $currentZone->phase + 1;
        $config = config('games.survival-arena.safe_zone');

        if ($nextPhase > count($config['phases'])) {
            return null;
        }

        $newRadius = $config['initial_radius'] - (($nextPhase - 1) * $config['shrink_amount']);
        $newRadius = max($newRadius, $config['min_radius']);

        $phaseConfig = $config['phases'][$nextPhase] ?? $config['phases'][count($config['phases'])];

        $newZone = $match->safeZones()->create([
            'phase' => $nextPhase,
            'center' => ['x' => 0, 'z' => 0],
            'radius' => $newRadius,
            'damage_per_second' => $phaseConfig['damage'],
            'starts_at' => now(),
            'ends_at' => now()->addSeconds($phaseConfig['duration']),
        ]);

        broadcast(new SafeZoneUpdated($match, $newZone))->toOthers();

        return $newZone;
    }

    public function applyDamageToPlayersOutsideZone(ArenaMatch $match, Collection $players): void
    {
        $currentZone = $match->safeZones()->current()->first();

        if (!$currentZone) {
            return;
        }

        foreach ($players as $player) {
            if (!$this->isPlayerInsideZone($player, $currentZone)) {
                $player->takeDamage($currentZone->damage_per_second);
            }
        }
    }

    private function isPlayerInsideZone(PlayerState $player, SafeZone $zone): bool
    {
        $distance = sqrt(
            pow($player->position['x'] - $zone->center['x'], 2) +
            pow($player->position['z'] - $zone->center['z'], 2)
        );

        return $distance <= $zone->radius;
    }

    public function getSafeZoneData(ArenaMatch $match): ?array
    {
        $currentZone = $match->safeZones()->current()->first();

        return $currentZone?->toGameData();
    }
}

