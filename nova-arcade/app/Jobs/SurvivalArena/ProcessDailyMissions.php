<?php

namespace App\Jobs\SurvivalArena;

use App\Models\User;
use App\Models\SurvivalArena\DailyMission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDailyMissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Generating daily missions for all users');

        $missionTypes = [
            [
                'mission_type' => 'play_matches',
                'description' => 'Play 5 matches',
                'target' => 5,
                'reward_xp' => 500
            ],
            [
                'mission_type' => 'get_kills',
                'description' => 'Get 10 kills',
                'target' => 10,
                'reward_xp' => 300
            ],
            [
                'mission_type' => 'win_match',
                'description' => 'Win 1 match',
                'target' => 1,
                'reward_xp' => 1000
            ],
            [
                'mission_type' => 'deal_damage',
                'description' => 'Deal 2000 damage',
                'target' => 2000,
                'reward_xp' => 400
            ],
            [
                'mission_type' => 'top_5_placement',
                'description' => 'Finish in top 5 three times',
                'target' => 3,
                'reward_xp' => 600
            ]
        ];

        // Generate missions for active users
        User::whereHas('matchPlayers', function ($q) {
            $q->where('created_at', '>', now()->subDays(7));
        })->chunk(100, function ($users) use ($missionTypes) {
            foreach ($users as $user) {
                // Give each user 3 random missions
                $selectedMissions = collect($missionTypes)->random(3);

                foreach ($selectedMissions as $mission) {
                    DailyMission::create([
                        'user_id' => $user->id,
                        'date' => today(),
                        'mission_type' => $mission['mission_type'],
                        'description' => $mission['description'],
                        'target' => $mission['target'],
                        'reward_xp' => $mission['reward_xp']
                    ]);
                }
            }
        });

        Log::info('Daily missions generated');
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['daily-missions', 'maintenance'];
    }
}