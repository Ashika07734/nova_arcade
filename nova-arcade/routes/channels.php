<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\SurvivalArena\Match;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Match channel - only players in match can listen
Broadcast::channel('survival-arena.match.{matchId}', function ($user, $matchId) {
    $match = Match::find($matchId);
    
    if (!$match) {
        return false;
    }
    
    return $match->players()
        ->where('user_id', $user->id)
        ->exists();
});

// User private channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});