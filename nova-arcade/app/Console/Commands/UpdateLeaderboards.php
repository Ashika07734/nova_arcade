<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SurvivalArena\Leaderboard;

class UpdateLeaderboards extends Command
{
    protected $signature = 'leaderboards:update {--period=all_time} {--category=all}';
    protected $description = 'Update game leaderboards';

    public function handle()
    {
        $period = $this->option('period');
        $category = $this->option('category');

        $categories = $category === 'all' 
            ? ['wins', 'kills', 'kd_ratio', 'damage']
            : [$category];

        $this->info("Updating leaderboards for period: {$period}");

        foreach ($categories as $cat) {
            $this->info("  - Updating {$cat}...");
            Leaderboard::updateRankings($period, $cat);
        }

        $this->info('Leaderboards updated successfully.');
    }
}