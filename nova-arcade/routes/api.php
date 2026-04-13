<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SurvivalArena\GameplayController;
use App\Http\Controllers\SurvivalArena\PlayerController;
use App\Http\Controllers\SurvivalArena\WeaponController;
use App\Http\Controllers\SurvivalArena\StatsController;
use App\Http\Controllers\Web\LeaderboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    
    Route::prefix('survival-arena')->name('survival-arena.')->group(function () {
        
        // Match State
        Route::get('/matches/{match}/state', [GameplayController::class, 'getState'])
            ->name('matches.state');
        
        // Player Actions
        Route::post('/matches/{match}/position', [PlayerController::class, 'updatePosition'])
            ->middleware('throttle:600,1') // 600 requests per minute (10/sec)
            ->name('player.position');
        
        Route::post('/matches/{match}/shoot', [GameplayController::class, 'shoot'])
            ->middleware('throttle:120,1') // 120 requests per minute (2/sec)
            ->name('player.shoot');
        
        Route::post('/matches/{match}/pickup', [GameplayController::class, 'pickupItem'])
            ->middleware('throttle:60,1')
            ->name('player.pickup');
        
        Route::post('/matches/{match}/reload', [GameplayController::class, 'reload'])
            ->middleware('throttle:60,1')
            ->name('player.reload');
        
        Route::post('/matches/{match}/switch-weapon', [GameplayController::class, 'switchWeapon'])
            ->middleware('throttle:60,1')
            ->name('player.switch-weapon');
        
        // Weapons Data
        Route::get('/weapons', [WeaponController::class, 'index'])
            ->name('weapons.index');
        
        // Statistics
        Route::get('/stats/{user}', [StatsController::class, 'show'])
            ->name('stats.show');
        
        Route::get('/leaderboards/{period}/{category}', [StatsController::class, 'leaderboard'])
            ->name('leaderboards');
    });
});
// Leaderboards
Route::get('/leaderboards', [LeaderboardController::class, 'index'])
    ->name('leaderboards.index');

Route::get('/leaderboards/{period}/{category}', [LeaderboardController::class, 'show'])
    ->name('leaderboards.show');

Route::get('/leaderboards/user/{user}', [LeaderboardController::class, 'userRank'])
    ->name('leaderboards.user');