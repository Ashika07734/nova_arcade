<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('games.survival-arena.name') }} - Match {{ $match->match_code }}</title>
    
    <!-- Game CSS -->
    <link rel="stylesheet" href="{{ asset('games/survival-arena-3d/css/game.css') }}">
</head>
<body>
    <!-- Game Canvas Container -->
    <div id="game-container"></div>
    
    <!-- HUD Overlay -->
    <div id="game-hud">
        <!-- Top Bar -->
        <div class="hud-top">
            <div class="player-count">
                <span class="icon">PLY</span>
                <span id="alive-count">{{ $match->current_players }}</span> Alive
            </div>
            
            <div class="kill-feed" id="kill-feed"></div>
            
            <div class="pollution-timer">
                <div class="timer-label">Zone Closing</div>
                <div class="timer-value" id="zone-timer">60s</div>
                <div class="pollution-bar">
                    <div class="pollution-fill" id="pollution-fill"></div>
                </div>
            </div>

            <div class="pollution-timer">
                <div class="timer-label">Match Ends</div>
                <div class="timer-value" id="match-timer">5:00</div>
            </div>
        </div>
        
        <!-- Crosshair -->
        <div class="crosshair">
            <div class="crosshair-dot"></div>
            <div class="crosshair-line crosshair-top"></div>
            <div class="crosshair-line crosshair-bottom"></div>
            <div class="crosshair-line crosshair-left"></div>
            <div class="crosshair-line crosshair-right"></div>
        </div>
        
        <!-- Bottom Left - Health & Shield -->
        <div class="hud-bottom-left">
            <div class="health-container">
                <div class="stat-label">Health</div>
                <div class="health-bar">
                    <div class="health-fill" id="health-fill"></div>
                    <span class="health-value" id="health-value">100</span>
                </div>
            </div>
            
            <div class="shield-container">
                <div class="stat-label">Shield</div>
                <div class="shield-bar">
                    <div class="shield-fill" id="shield-fill"></div>
                    <span class="shield-value" id="shield-value">100</span>
                </div>
            </div>
        </div>
        
        <!-- Bottom Center - Weapon Info -->
        <div class="hud-bottom-center">
            <div class="weapon-info">
                <div class="weapon-name" id="weapon-name">Assault Rifle</div>
                <div class="ammo-container">
                    <span class="ammo-current" id="ammo-current">30</span>
                    <span class="ammo-divider">/</span>
                    <span class="ammo-reserve" id="ammo-reserve">120</span>
                </div>
            </div>
            
            <div class="reload-indicator" id="reload-indicator">
                <div class="reload-text">RELOADING</div>
                <div class="reload-progress">
                    <div class="reload-fill"></div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Right - Minimap -->
        <div class="hud-bottom-right">
            <div class="minimap-container">
                <canvas id="minimap" width="200" height="200"></canvas>
            </div>
            
            <div class="kills-counter">
                <span class="kills-icon">K</span>
                <span id="kills-count">0</span> Kills
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="notifications" id="notifications"></div>
        
        <!-- Damage Indicators -->
        <div class="damage-indicators" id="damage-indicators"></div>
    </div>
    
    <!-- Loading Screen -->
    <div id="loading-screen">
        <div class="loading-content">
            <h1>{{ config('games.survival-arena.name') }}</h1>
            <div class="loading-bar">
                <div class="loading-fill" id="loading-fill"></div>
            </div>
            <p class="loading-text" id="loading-text">Loading assets...</p>
        </div>
    </div>
    
    <!-- Death Screen -->
    <div id="death-screen" class="hidden">
        <div class="death-content">
            <h1 class="death-title">YOU DIED</h1>
            <div class="death-stats">
                <div class="stat">
                    <span class="stat-label">Placement</span>
                    <span class="stat-value" id="placement">#5</span>
                </div>
                <div class="stat">
                    <span class="stat-label">Kills</span>
                    <span class="stat-value" id="final-kills">3</span>
                </div>
                <div class="stat">
                    <span class="stat-label">Survival Time</span>
                    <span class="stat-value" id="survival-time">5:32</span>
                </div>
            </div>
            <button class="btn-spectate">Spectate</button>
            <a href="{{ route('survival-arena.matchmaking') }}" class="btn-leave">Play Again</a>
        </div>
    </div>
    
    <!-- Victory Screen -->
    <div id="victory-screen" class="hidden">
        <div class="victory-content">
            <h1 class="victory-title">VICTORY!</h1>
            <div class="confetti"></div>
            <div class="victory-stats">
                <div class="stat">
                    <span class="stat-label">Placement</span>
                    <span class="stat-value" id="win-placement">#1</span>
                </div>
                <div class="stat">
                    <span class="stat-label">Kills</span>
                    <span class="stat-value" id="win-kills">12</span>
                </div>
                <div class="stat">
                    <span class="stat-label">XP Earned</span>
                    <span class="stat-value" id="win-xp">+850</span>
                </div>
            </div>
            <a href="{{ route('survival-arena.matchmaking') }}" class="btn-continue">Play Again</a>
        </div>
    </div>
    
    <!-- Pass data to JavaScript -->
    <script>
        window.gameData = {
            matchId: {{ $match->id }},
            matchCode: "{{ $match->match_code }}",
            userId: {{ auth()->id() }},
            userName: "{{ auth()->user()->username ?? auth()->user()->name }}",
            apiBaseUrl: "{{ url('/api/survival-arena') }}",
            wsUrl: "{{ env('REVERB_HOST', 'localhost') }}:{{ env('REVERB_PORT', 8080) }}",
            csrfToken: "{{ csrf_token() }}",
            matchDurationSeconds: {{ config('games.survival-arena.timing.max_duration_seconds', 300) }},
            matchStartedAt: "{{ optional($match->started_at)->toIso8601String() ?? now()->toIso8601String() }}",
            difficulty: "{{ $match->difficulty ?? 'easy' }}",
            botCount: {{ $match->bot_count ?? 3 }},
            mapData: @json($match->map_data)
        };
    </script>
    
    <!-- Game Scripts (bundled locally via Vite, no CDN dependency) -->
    @vite('public/games/survival-arena-3d/js/main-city.js')
</body>
</html>