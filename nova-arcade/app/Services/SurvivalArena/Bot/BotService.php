<?php

namespace App\Services\SurvivalArena\Bot;

use App\Models\SurvivalArena\ArenaMatch;
use App\Models\SurvivalArena\PlayerState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BotService
{
    public function __construct(
        private BotMovementService $botMovementService,
        private BotCombatService $botCombatService
    ) {}

    public function updateBots(ArenaMatch $match, Collection $playerStates): void
    {
        if (($match->mode ?? $match->game_mode) !== 'solo') {
            return;
        }

        $human = $playerStates->first(function (PlayerState $state) {
            return !$state->is_bot && $state->health > 0;
        });

        if (!$human) {
            return;
        }

        $bots = $playerStates->filter(function (PlayerState $state) {
            return $state->is_bot && $state->health > 0;
        });

        $difficulty = $match->difficulty ?? 'easy';

        foreach ($bots as $bot) {
            $context = $this->getBotContext($match->id, $bot);

            $context = $this->botMovementService->updateMovement($bot, $human, $difficulty, $context);
            $context = $this->botCombatService->tryShoot($bot, $human, $difficulty, $context);

            $this->storeBotContext($match->id, $bot, $context);

            $bot->last_updated = now();
            $bot->save();
        }
    }

    public function livingBots(ArenaMatch $match): int
    {
        return $match->players()
            ->where('is_bot', true)
            ->where('is_alive', true)
            ->count();
    }

    private function contextKey(int $matchId, PlayerState $bot): string
    {
        return "match:{$matchId}:bot_ai:{$bot->id}";
    }

    private function getBotContext(int $matchId, PlayerState $bot): array
    {
        return Cache::get($this->contextKey($matchId, $bot), [
            'state' => 'patrol',
            'strafe_direction' => (($bot->id % 2) === 0 ? 1 : -1),
            'next_strafe_switch_at' => microtime(true) + 2.5,
            'next_shot_at' => microtime(true) + 0.8,
        ]);
    }

    private function storeBotContext(int $matchId, PlayerState $bot, array $context): void
    {
        Cache::put($this->contextKey($matchId, $bot), $context, now()->addMinutes(15));
    }
}
