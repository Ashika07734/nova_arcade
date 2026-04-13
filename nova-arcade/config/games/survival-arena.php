<?php

return [
    'name' => 'Survival Arena 3D',
    'slug' => 'survival-arena-3d',
    'version' => '1.0.0',
    'enabled' => env('GAME_SURVIVAL_ARENA_ENABLED', true),
    
    // Game Mechanics
    'tick_rate' => env('GAME_TICK_RATE', 60),
    'send_rate' => env('GAME_SEND_RATE', 20),
    
    'physics' => [
        'gravity' => -9.81,
        'player_speed' => 5.0,
        'sprint_multiplier' => 1.5,
        'jump_force' => 5.0,
        'max_fall_speed' => -50.0,
    ],
    
    'combat' => [
        'damage_falloff_start' => 50,
        'damage_falloff_end' => 100,
        'headshot_multiplier' => 2.0,
        'max_bullet_range' => 200,
    ],
    
    'safe_zone' => [
        'initial_radius' => 100,
        'shrink_interval' => 60,
        'shrink_amount' => 15,
        'min_radius' => 10,
        'phases' => [
            1 => ['damage' => 5, 'duration' => 60],
            2 => ['damage' => 10, 'duration' => 50],
            3 => ['damage' => 20, 'duration' => 40],
            4 => ['damage' => 50, 'duration' => 30],
        ]
    ],
    
    'matchmaking' => [
        'min_players' => 2,
        'min_players_by_mode' => [
            'solo' => 1,
            'duo' => 2,
            'squad' => 4,
        ],
        'stale_in_progress_minutes' => 30,
        'max_players' => 50,
        'start_delay' => 10,
        'max_wait_time' => 60,
    ],
    
    // Assets
    'assets' => [
        'models_path' => '/assets/models',
        'textures_path' => '/assets/textures',
        'sounds_path' => '/assets/sounds',
        'images_path' => '/assets/images',
    ],
    
    // CDN (if using)
    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'url' => env('CDN_URL', ''),
    ],
];