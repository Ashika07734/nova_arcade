<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\LeaderboardController;
use App\Http\Controllers\SurvivalArena\InventoryController;
use App\Http\Controllers\SurvivalArena\MatchController;
use App\Http\Controllers\SurvivalArena\LobbyController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/how-to-play', [HomeController::class, 'howToPlay'])->name('how-to-play');
Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
Route::get('/stats', [HomeController::class, 'stats'])->name('stats');
Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'submitContact'])->name('contact.submit');
Route::get('/leaderboards', [LeaderboardController::class, 'index'])->name('leaderboards');

// Auth Routes (Laravel Breeze/Jetstream)
require __DIR__.'/auth.php';

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Profile and account
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::get('/profile/{username}', [ProfileController::class, 'show'])
        ->name('profile.show');
    Route::get('/settings', [ProfileController::class, 'settings'])
        ->name('settings');
    Route::post('/settings', [ProfileController::class, 'update'])
        ->name('settings.update');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])
        ->name('inventory');
    Route::post('/inventory/equip', [InventoryController::class, 'equip'])
        ->name('inventory.equip');
    Route::post('/inventory/unequip', [InventoryController::class, 'unequip'])
        ->name('inventory.unequip');
    
    // Survival Arena Game Routes
    Route::prefix('survival-arena')->name('survival-arena.')->group(function () {
        
        // Landing page for the game
        Route::get('/', [MatchController::class, 'landing'])
            ->name('landing');
        
        // Matchmaking
        Route::get('/matchmaking', [MatchController::class, 'matchmaking'])
            ->name('matchmaking');
        Route::post('/matchmaking/join', [MatchController::class, 'joinQueue'])
            ->name('matchmaking.join');
        Route::post('/matchmaking/leave', [MatchController::class, 'leaveQueue'])
            ->name('matchmaking.leave');
        
        // Match Management
        Route::get('/matches/create', [MatchController::class, 'create'])
            ->name('matches.create');
        Route::post('/matches', [MatchController::class, 'store'])
            ->name('matches.store');
        Route::get('/matches/{match}/join', [MatchController::class, 'join'])
            ->name('matches.join');
        
        // Lobby
        Route::get('/matches/{match}/lobby', [LobbyController::class, 'show'])
            ->name('lobby');
        Route::post('/matches/{match}/ready', [LobbyController::class, 'toggleReady'])
            ->name('lobby.ready');
        Route::post('/matches/{match}/start', [LobbyController::class, 'start'])
            ->name('lobby.start');
        Route::post('/matches/{match}/leave', [LobbyController::class, 'leave'])
            ->name('lobby.leave');
        
        // Game Play
        Route::get('/matches/{match}/play', [MatchController::class, 'play'])
            ->middleware('check.game.session')
            ->name('play');
        
        // Results
        Route::get('/matches/{match}/results', [MatchController::class, 'results'])
            ->name('results');
    });
});