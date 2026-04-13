<?php

namespace App\Console;

use App\Jobs\SurvivalArena\CleanupExpiredMatches;
use App\Jobs\SurvivalArena\ProcessDailyMissions;
use App\Jobs\SurvivalArena\UpdateLeaderboards;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new UpdateLeaderboards())
            ->hourly()
            ->name('update-leaderboards')
            ->withoutOverlapping();

        $schedule->job(new CleanupExpiredMatches())
            ->daily()
            ->name('cleanup-matches')
            ->withoutOverlapping();

        $schedule->job(new ProcessDailyMissions())
            ->dailyAt('00:00')
            ->name('daily-missions')
            ->withoutOverlapping();

        $schedule->command('backup:run')
            ->dailyAt('02:00')
            ->name('database-backup');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}