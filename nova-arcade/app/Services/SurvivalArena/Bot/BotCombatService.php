<?php

namespace App\Services\SurvivalArena\Bot;

use App\Models\SurvivalArena\PlayerState;
use App\Models\SurvivalArena\Weapon;
use App\Services\SurvivalArena\Combat\WeaponService;

class BotCombatService
{
    public function __construct(
        private WeaponService $weaponService
    ) {}

    public function tryShoot(
        PlayerState $bot,
        PlayerState $player,
        string $difficulty = 'easy',
        array $context = []
    ): array
    {
        $now = microtime(true);
        $profile = $this->difficultyProfile($difficulty);

        if (($context['next_shot_at'] ?? 0) > $now) {
            return $context;
        }

        $dx = ($player->position['x'] ?? 0) - ($bot->position['x'] ?? 0);
        $dy = (($player->position['y'] ?? 1) + 0.8) - (($bot->position['y'] ?? 1) + 0.8);
        $dz = ($player->position['z'] ?? 0) - ($bot->position['z'] ?? 0);
        $distance = sqrt(($dx * $dx) + ($dz * $dz));

        if ($distance > $profile['shoot_range']) {
            return $context;
        }

        $weapon = $this->resolveWeapon();
        if (!$weapon) {
            return $context;
        }

        // Apply controlled inaccuracy by difficulty.
        $aimError = $profile['aim_error'];
        $dx += ((mt_rand(0, 1000) / 1000) - 0.5) * $aimError;
        $dy += ((mt_rand(0, 1000) / 1000) - 0.5) * ($aimError * 0.35);
        $dz += ((mt_rand(0, 1000) / 1000) - 0.5) * $aimError;

        $direction = [
            'x' => $dx,
            'y' => $dy,
            'z' => $dz,
        ];

        // Lower spread value means better accuracy.
        $weapon->spread = max(0.005, $weapon->spread + $profile['spread_penalty']);

        $this->weaponService->shoot($bot, $direction, $weapon);

        $context['next_shot_at'] = $now + $profile['shot_cooldown'] + (mt_rand(0, 100) / 1000);

        return $context;
    }

    private function resolveWeapon(): ?Weapon
    {
        return Weapon::query()->orderBy('damage', 'desc')->first()
            ?? Weapon::query()->first();
    }

    private function difficultyProfile(string $difficulty): array
    {
        return match ($difficulty) {
            'hard' => [
                'shoot_range' => 34,
                'spread_penalty' => -0.015,
                'shot_cooldown' => 0.22,
                'aim_error' => 0.04,
            ],
            'medium' => [
                'shoot_range' => 28,
                'spread_penalty' => -0.005,
                'shot_cooldown' => 0.36,
                'aim_error' => 0.08,
            ],
            default => [
                'shoot_range' => 22,
                'spread_penalty' => 0.012,
                'shot_cooldown' => 0.52,
                'aim_error' => 0.16,
            ],
        };
    }
}
