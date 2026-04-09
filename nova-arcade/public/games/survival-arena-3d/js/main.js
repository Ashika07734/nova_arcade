// placeholder
// public/games/survival-arena-3d/js/main.js
import { GAME_CONFIG } from './config.js';
import { GameEngine } from './core/GameEngine.js';
import { NetworkManager } from './services/NetworkManager.js';
import { HUDManager } from './ui/HUDManager.js';
import { AudioManager } from './core/AudioManager.js';

class SurvivalArenaGame {
    constructor() {
        this.config = GAME_CONFIG;
        this.engine = null;
        this.network = null;
        this.hud = null;
        this.audio = null;
        this.isRunning = false;
        this.isPaused = false;
    }

    async init() {
        console.log('🎮 Initializing Survival Arena 3D...');
        console.log('Match ID:', this.config.matchId);
        console.log('Player:', this.config.userName);

        try {
            // Show loading screen
            this.updateLoadingScreen('Initializing game...', 10);

            // Initialize audio
            this.audio = new AudioManager();
            await this.audio.init();
            this.updateLoadingScreen('Audio system ready', 20);

            // Initialize network
            this.network = new NetworkManager(this.config);
            await this.network.init();
            this.updateLoadingScreen('Connected to server', 40);

            // Initialize 3D engine
            this.engine = new GameEngine(this.config);
            await this.engine.init();
            this.updateLoadingScreen('3D engine ready', 60);

            // Initialize HUD
            this.hud = new HUDManager();
            this.hud.init();
            this.updateLoadingScreen('UI ready', 80);

            // Connect to match
            this.network.joinMatch(this.config.matchId);
            this.updateLoadingScreen('Joining match...', 90);

            // Setup event listeners
            this.setupEventListeners();
            this.updateLoadingScreen('Loading complete!', 100);

            // Hide loading screen after short delay
            setTimeout(() => {
                this.hideLoadingScreen();
                this.start();
            }, 1000);

        } catch (error) {
            console.error('❌ Failed to initialize game:', error);
            alert('Failed to start game. Please refresh the page.');
        }
    }

    setupEventListeners() {
        // Network events
        this.network.on('playerJoined', (player) => {
            console.log(`➕ ${player.username} joined`);
            this.engine.addPlayer(player);
            this.hud.showNotification(`${player.username} joined the match`, 'info');
        });

        this.network.on('playerLeft', (playerId) => {
            this.engine.removePlayer(playerId);
        });

        this.network.on('gameStateUpdate', (state) => {
            this.engine.updateGameState(state);
            this.hud.updateAliveCount(state.alive_players);
        });

        this.network.on('playerMoved', (data) => {
            this.engine.updatePlayerPosition(
                data.player_id,
                data.position,
                data.rotation
            );
        });

        this.network.on('playerShot', (data) => {
            this.engine.showShotEffect(data.player_id, data.direction);
            if (data.player_id !== this.config.userId) {
                this.audio.play3DSound('gunshot', data.position);
            }
        });

        this.network.on('playerDamaged', (data) => {
            this.engine.showDamageEffect(data.player_id, data.damage);
            
            if (data.player_id === this.config.userId) {
                this.hud.updateHealth(data.health, data.shield);
                this.hud.flashDamageIndicator(data.direction);
                this.audio.playSound('hit_received');
            }
        });

        this.network.on('playerKilled', (data) => {
            this.engine.killPlayer(data.victim_id);
            this.hud.addKillFeedEntry(
                data.killer_name,
                data.victim_name,
                data.weapon_name
            );
            
            if (data.killer_id === this.config.userId) {
                this.hud.showKillConfirmation();
                this.audio.playSound('kill');
            } else if (data.victim_id === this.config.userId) {
                this.handlePlayerDeath(data);
            }
        });

        this.network.on('safeZoneUpdated', (data) => {
            this.engine.updateSafeZone(data.center, data.radius);
            this.hud.updateZoneTimer(data.time_remaining);
            
            if (data.phase > 1) {
                this.hud.showNotification(
                    `Zone shrinking! Phase ${data.phase}`,
                    'warning'
                );
                this.audio.playSound('zone_warning');
            }
        });

        this.network.on('matchEnded', (data) => {
            this.endMatch(data);
        });

        // Engine events
        this.engine.on('localPlayerMoved', (position, rotation) => {
            this.network.sendPlayerPosition(position, rotation);
        });

        this.engine.on('localPlayerShot', (direction, weaponId) => {
            this.network.sendShootEvent(direction, weaponId);
            this.audio.playSound('gunshot');
        });

        this.engine.on('itemPickedUp', (itemId) => {
            this.network.sendPickupItem(itemId);
            this.audio.playSound('item_pickup');
        });

        this.engine.on('weaponChanged', (weapon) => {
            this.hud.updateWeapon(weapon);
        });

        this.engine.on('ammoChanged', (current, reserve) => {
            this.hud.updateAmmo(current, reserve);
        });

        // Window events
        window.addEventListener('beforeunload', (e) => {
            if (this.isRunning) {
                e.preventDefault();
                e.returnValue = 'Are you sure you want to leave the match?';
            }
        });

        // Pause on visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pause();
            } else {
                this.resume();
            }
        });
    }

    start() {
        this.isRunning = true;
        this.gameLoop();
        console.log('✅ Game started!');
    }

    pause() {
        this.isPaused = true;
        this.engine.pause();
    }

    resume() {
        this.isPaused = false;
        this.engine.resume();
    }

    gameLoop() {
        if (!this.isRunning) return;

        if (!this.isPaused) {
            // Update engine (renders at 60fps internally)
            this.engine.update();

            // Update HUD
            const localPlayer = this.engine.getLocalPlayer();
            if (localPlayer) {
                this.hud.update(localPlayer);
            }
        }

        requestAnimationFrame(() => this.gameLoop());
    }

    handlePlayerDeath(data) {
        this.pause();
        this.hud.showDeathScreen({
            placement: data.placement,
            kills: data.kills,
            survivalTime: data.survival_time,
            xpEarned: data.xp_earned
        });
        this.audio.playSound('death');
    }

    endMatch(data) {
        this.isRunning = false;
        this.pause();

        if (data.winner_id === this.config.userId) {
            this.hud.showVictoryScreen({
                kills: data.kills,
                survivalTime: data.survival_time,
                xpEarned: data.xp_earned
            });
            this.audio.playSound('victory');
        }
    }

    updateLoadingScreen(text, progress) {
        const textEl = document.getElementById('loading-text');
        const fillEl = document.getElementById('loading-fill');
        
        if (textEl) textEl.textContent = text;
        if (fillEl) fillEl.style.width = `${progress}%`;
    }

    hideLoadingScreen() {
        const screen = document.getElementById('loading-screen');
        if (screen) {
            screen.classList.add('hidden');
        }
    }
}

// Initialize game when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const game = new SurvivalArenaGame();
        game.init();
    });
} else {
    const game = new SurvivalArenaGame();
    game.init();
}
