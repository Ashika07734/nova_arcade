import * as THREE from 'three';
import { Bot } from '../entities/Bot.js';

/**
 * Full client-side Bot AI manager.
 * Runs patrol → chase → engage behaviour per bot every frame,
 * including shooting at the player (with difficulty-scaled accuracy)
 * so the match is playable without a server-side game-tick loop.
 */
export class BotAI {
    constructor(scene, options = {}) {
        this.scene = scene;
        this.bots = new Map();

        this.difficulty = options.difficulty || 'easy';
        this.worldSize = options.worldSize || 120;
        this.playerRef = null;          // set externally
        this.onBotShootPlayer = null;   // callback(botKey, damage, headshot)
        this.onBotDied = null;          // callback(botKey, killerName)

        this.profile = BotAI.difficultyProfile(this.difficulty);
    }

    /* ------- difficulty profiles ------- */
    static difficultyProfile(difficulty) {
        const profiles = {
            hard: {
                moveSpeed: 5.8, patrolSpeed: 2.0, strafeSpeed: 2.7,
                chaseRange: 70, engageRange: 28, idealDist: 16,
                shootRange: 36, shotCooldown: 0.28, aimError: 0.06,
                baseDamage: 12, headshotChance: 0.18,
                reactionDelay: 0.2,
            },
            medium: {
                moveSpeed: 4.8, patrolSpeed: 1.6, strafeSpeed: 2.0,
                chaseRange: 60, engageRange: 22, idealDist: 14,
                shootRange: 30, shotCooldown: 0.45, aimError: 0.12,
                baseDamage: 9, headshotChance: 0.10,
                reactionDelay: 0.4,
            },
            easy: {
                moveSpeed: 3.8, patrolSpeed: 1.2, strafeSpeed: 1.4,
                chaseRange: 50, engageRange: 18, idealDist: 12,
                shootRange: 24, shotCooldown: 0.65, aimError: 0.22,
                baseDamage: 6, headshotChance: 0.04,
                reactionDelay: 0.6,
            },
        };
        return profiles[difficulty] || profiles.easy;
    }

    /* ------- sync from server (optional, kept for compatibility) ------- */
    sync(botStates) {
        const seen = new Set();

        for (const state of botStates) {
            const key = state.state_id ?? state.bot_name ?? `bot-${Math.random()}`;
            seen.add(key);

            if (!this.bots.has(key)) {
                const bot = new Bot(key, state.username || 'BOT');
                this.bots.set(key, bot);
                this.scene.add(bot.group);
                // init local AI context
                bot._ai = this._newAIContext(state);
            }

            const bot = this.bots.get(key);
            // Only apply server position if we don't have local AI running yet
            if (!bot._ai) {
                bot.updateFromState(state);
                bot._ai = this._newAIContext(state);
            } else {
                // Keep health & alive from server if more authoritative
                if (state.health !== undefined) bot._ai.health = state.health;
                if (state.is_alive === false) {
                    bot.isAlive = false;
                    bot._ai.alive = false;
                }
            }
        }

        for (const [key, bot] of this.bots.entries()) {
            if (!seen.has(key)) {
                this.scene.remove(bot.group);
                bot.dispose();
                this.bots.delete(key);
            }
        }
    }

    /* ------- spawn bots locally when server provides none ------- */
    spawnLocalBots(count, spawnPoints = []) {
        const names = [
            'Phantom', 'Nova', 'Blitz', 'Spectre', 'Viper',
            'Shadow', 'Echo', 'Frost', 'Storm', 'Raven',
            'Cobra', 'Wolf', 'Hawk', 'Reaper', 'Ghost',
        ];
        const fallbackSpawns = [
            { x: -38, z: -24 }, { x: 28, z: -14 }, { x: -6, z: -42 },
            { x: 44, z: 42 }, { x: -52, z: 16 }, { x: -18, z: 38 },
            { x: 4, z: -6 }, { x: 58, z: 8 },
        ];

        for (let i = 0; i < count; i++) {
            const key = `local-bot-${i}`;
            if (this.bots.has(key)) continue;

            const name = names[i % names.length];
            const bot = new Bot(key, name);
            const sp = spawnPoints[i % Math.max(1, spawnPoints.length)]
                || fallbackSpawns[i % fallbackSpawns.length];

            bot.group.position.set(sp.x || 0, 0.05, sp.z || 0);
            bot._ai = this._newAIContext({
                position: { x: sp.x || 0, y: 0.05, z: sp.z || 0 },
                health: 100,
                is_alive: true,
                username: name,
            });
            this.bots.set(key, bot);
            this.scene.add(bot.group);
        }
    }

    _newAIContext(state) {
        return {
            alive: (state.is_alive !== false) && ((state.health ?? 100) > 0),
            health: state.health ?? 100,
            state: 'patrol',
            strafeDir: Math.random() > 0.5 ? 1 : -1,
            nextStrafeSwitch: performance.now() / 1000 + 2 + Math.random() * 2,
            nextShotAt: performance.now() / 1000 + this.profile.reactionDelay + Math.random(),
            patrolAngle: Math.random() * Math.PI * 2,
            name: state.username || state.bot_name || 'BOT',
        };
    }

    /* ------- per-frame update ------- */
    update(delta) {
        if (!this.playerRef) return;

        const now = performance.now() / 1000;
        const playerPos = this.playerRef.position;

        for (const [key, bot] of this.bots.entries()) {
            bot.update(delta, this._cameraRef);

            const ai = bot._ai;
            if (!ai || !ai.alive) continue;

            // Distance to player
            const dx = playerPos.x - bot.group.position.x;
            const dz = playerPos.z - bot.group.position.z;
            const dist = Math.sqrt(dx * dx + dz * dz);
            const safeD = Math.max(dist, 0.01);

            // Face the player when close enough
            if (dist < this.profile.chaseRange) {
                bot.group.rotation.y = Math.atan2(dx, dz);
            }

            // --- movement state machine ---
            let vx = 0, vz = 0;

            if (dist < this.profile.engageRange) {
                ai.state = 'engage';
                // Strafe
                if (now >= ai.nextStrafeSwitch) {
                    ai.strafeDir *= -1;
                    ai.nextStrafeSwitch = now + 1.5 + Math.random() * 2;
                }
                const strafe = ai.strafeDir * this.profile.strafeSpeed;
                vx = (-dz / safeD) * strafe;
                vz = (dx / safeD) * strafe;

                // Maintain ideal distance
                const distDelta = dist - this.profile.idealDist;
                vx += (dx / safeD) * distDelta * 0.3;
                vz += (dz / safeD) * distDelta * 0.3;
            } else if (dist < this.profile.chaseRange) {
                ai.state = 'chase';
                vx = (dx / safeD) * this.profile.moveSpeed;
                vz = (dz / safeD) * this.profile.moveSpeed;
            } else {
                ai.state = 'patrol';
                ai.patrolAngle += delta * 0.35;
                vx = Math.cos(ai.patrolAngle) * this.profile.patrolSpeed;
                vz = Math.sin(ai.patrolAngle) * this.profile.patrolSpeed;
            }

            // Apply movement
            const half = this.worldSize * 0.42;
            bot.group.position.x = Math.max(-half, Math.min(half, bot.group.position.x + vx * delta));
            bot.group.position.z = Math.max(-half, Math.min(half, bot.group.position.z + vz * delta));
            bot.group.position.y = Math.max(0.05, bot.group.position.y);

            // --- shooting ---
            if (dist <= this.profile.shootRange && now >= ai.nextShotAt) {
                this._botFireAtPlayer(bot, key, dx, dz, dist);
                ai.nextShotAt = now + this.profile.shotCooldown + Math.random() * 0.15;
            }
        }
    }

    _botFireAtPlayer(bot, key, dx, dz, dist) {
        // Muzzle flash
        const flashPos = bot.group.position.clone();
        flashPos.y += 0.7;
        flashPos.x += (dx / Math.max(dist, 1)) * 0.5;
        flashPos.z += (dz / Math.max(dist, 1)) * 0.5;
        const flash = new THREE.PointLight(0xfca5a5, 1.4, 8, 2);
        flash.position.copy(flashPos);
        this.scene.add(flash);
        setTimeout(() => this.scene.remove(flash), 60);

        // Play bot shot sound
        this._playBotShot();

        // Hit check — apply aim error
        const miss = Math.random() < (this.profile.aimError * 3);
        if (miss) return;

        const headshot = Math.random() < this.profile.headshotChance;
        const dmg = headshot
            ? Math.round(this.profile.baseDamage * 2)
            : this.profile.baseDamage;

        if (this.onBotShootPlayer) {
            this.onBotShootPlayer(key, dmg, headshot);
        }
    }

    _playBotShot() {
        try {
            const AC = window.AudioContext || window.webkitAudioContext;
            if (!AC) return;
            if (!this._audioCtx) this._audioCtx = new AC();
            const osc = this._audioCtx.createOscillator();
            const gain = this._audioCtx.createGain();
            osc.type = 'square';
            osc.frequency.value = 260 + Math.random() * 80;
            gain.gain.value = 0.015;
            osc.connect(gain);
            gain.connect(this._audioCtx.destination);
            osc.start();
            osc.stop(this._audioCtx.currentTime + 0.03);
        } catch (_) { /* ignore audio errors */ }
    }

    /* ------- damage a bot (called when player shoots) ------- */
    damageBot(key, damage, headshot = false) {
        const bot = this.bots.get(key);
        if (!bot || !bot._ai || !bot._ai.alive) return null;

        bot._ai.health -= damage;

        // Visual feedback — flash and health bar update
        if (typeof bot.onDamaged === 'function') {
            bot.health = bot._ai.health;
            bot.onDamaged(0); // damage already applied, just trigger visuals
        } else {
            bot.health = bot._ai.health;
        }

        if (bot._ai.health <= 0) {
            bot._ai.health = 0;
            bot._ai.alive = false;
            bot.isAlive = false;
            bot.health = 0;
            bot.deathStarted = true;
            bot.deathProgress = 0;

            if (this.onBotDied) {
                this.onBotDied(key, bot._ai.name);
            }
            return { killed: true, name: bot._ai.name };
        }
        return { killed: false, name: bot._ai.name, remainingHealth: bot._ai.health };
    }

    /* ------- hit-test a ray against all alive bots ------- */
    raycastBots(origin, direction, maxDist = 120) {
        let closestHit = null;
        let closestDist = maxDist;

        for (const [key, bot] of this.bots.entries()) {
            if (!bot._ai || !bot._ai.alive) continue;

            const bp = bot.group.position;
            const toBot = new THREE.Vector3(bp.x - origin.x, bp.y + 0.85 - origin.y, bp.z - origin.z);
            const dot = toBot.dot(direction);
            if (dot < 0 || dot > closestDist) continue;

            const closest = origin.clone().add(direction.clone().multiplyScalar(dot));
            const perp = new THREE.Vector3(bp.x - closest.x, bp.y + 0.85 - closest.y, bp.z - closest.z);
            const perpDist = perp.length();

            if (perpDist <= 0.65) { // larger hitbox for better gameplay
                const headY = bp.y + 1.5;
                const hitY = closest.y;
                const headshot = hitY >= headY;

                closestDist = dot;
                closestHit = { key, distance: dot, headshot, bot };
            }
        }
        return closestHit;
    }

    /* ------- status ------- */
    getAliveCount() {
        let count = 0;
        for (const bot of this.bots.values()) {
            if (bot._ai && bot._ai.alive) count++;
        }
        return count;
    }

    getAllBotStates() {
        const states = [];
        for (const [key, bot] of this.bots.entries()) {
            if (!bot._ai) continue;
            states.push({
                key,
                name: bot._ai.name,
                alive: bot._ai.alive,
                health: bot._ai.health,
                position: {
                    x: bot.group.position.x,
                    y: bot.group.position.y,
                    z: bot.group.position.z,
                },
            });
        }
        return states;
    }

    clear() {
        for (const [, bot] of this.bots.entries()) {
            this.scene.remove(bot.group);
            bot.dispose();
        }
        this.bots.clear();
    }
}
