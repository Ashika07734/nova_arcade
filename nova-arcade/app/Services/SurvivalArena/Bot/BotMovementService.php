<?php

namespace App\Services\SurvivalArena\Bot;

use App\Models\SurvivalArena\PlayerState;

class BotMovementService
{
    public function updateMovement(
        PlayerState $bot,
        PlayerState $player,
        string $difficulty = 'easy',
        array $context = []
    ): array
    {
        $difficultyProfile = $this->difficultyProfile($difficulty);
        $now = microtime(true);

        $dx = ($player->position['x'] ?? 0) - ($bot->position['x'] ?? 0);
        $dz = ($player->position['z'] ?? 0) - ($bot->position['z'] ?? 0);
        $distance = sqrt(($dx * $dx) + ($dz * $dz));

        $velocity = $bot->velocity ?? ['x' => 0, 'y' => 0, 'z' => 0];
        $position = $bot->position;

        if ($distance <= 0.001) {
            $distance = 0.001;
        }

        if ($distance < $difficultyProfile['engage_range']) {
            $context['state'] = 'engage';

            if (($context['next_strafe_switch_at'] ?? 0) <= $now) {
                $context['strafe_direction'] = (($context['strafe_direction'] ?? 1) * -1);
                $context['next_strafe_switch_at'] = $now + mt_rand(14, 30) / 10;
            }

            $strafe = ($context['strafe_direction'] ?? 1) * $difficultyProfile['strafe_speed'];
            $velocity['x'] = (-$dz / $distance) * $strafe;
            $velocity['z'] = ($dx / $distance) * $strafe;

            // Keep distance in a soft band so bots don't constantly collide with player.
            $distanceDelta = $distance - $difficultyProfile['ideal_distance'];
            $velocity['x'] += ($dx / $distance) * $distanceDelta * 0.3;
            $velocity['z'] += ($dz / $distance) * $distanceDelta * 0.3;
        } elseif ($distance < $difficultyProfile['chase_range']) {
            $context['state'] = 'chase';
            $velocity['x'] = ($dx / $distance) * $difficultyProfile['move_speed'];
            $velocity['z'] = ($dz / $distance) * $difficultyProfile['move_speed'];
        } else {
            $context['state'] = 'patrol';
            // Idle patrol drift when player is far away.
            $angle = (microtime(true) + $bot->id) * 0.35;
            $velocity['x'] = cos($angle) * $difficultyProfile['patrol_speed'];
            $velocity['z'] = sin($angle) * $difficultyProfile['patrol_speed'];
        }

        $position['x'] = max(-95, min(95, ($position['x'] ?? 0) + ($velocity['x'] * (1 / 60))));
        $position['z'] = max(-95, min(95, ($position['z'] ?? 0) + ($velocity['z'] * (1 / 60))));
        $position['y'] = max(1, $position['y'] ?? 1);

        $bot->position = $position;
        $bot->velocity = $velocity;
        $bot->rotation = [
            'x' => 0,
            'y' => atan2($dx, $dz),
            'z' => 0,
        ];

        return $context;
    }

    private function difficultyProfile(string $difficulty): array
    {
        return match ($difficulty) {
            'hard' => [
                'move_speed' => 6.2,
                'patrol_speed' => 2.2,
                'strafe_speed' => 2.9,
                'chase_range' => 75,
                'engage_range' => 26,
                'ideal_distance' => 17,
            ],
            'medium' => [
                'move_speed' => 5.2,
                'patrol_speed' => 1.8,
                'strafe_speed' => 2.2,
                'chase_range' => 68,
                'engage_range' => 23,
                'ideal_distance' => 15,
            ],
            default => [
                'move_speed' => 4.4,
                'patrol_speed' => 1.4,
                'strafe_speed' => 1.7,
                'chase_range' => 60,
                'engage_range' => 20,
                'ideal_distance' => 14,
            ],
        };
    }
}
