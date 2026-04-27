import * as THREE from 'three';
import { CityMapLoader } from './world/CityMapLoader.js';
import { CollisionService } from './world/CollisionService.js';
import { BotAI } from './services/BotAI.js';
import { Player } from './entities/Player.js';
import { Weapon } from './entities/Weapon.js';
import { Bullet } from './entities/Bullet.js';

window.__survivalArenaBoot = {
    started: false,
    finished: false,
};

class SurvivalArenaCityGame {
    constructor() {
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.clock = new THREE.Clock();
        this.mapLoader = null;
        this.collisionService = new CollisionService();
        this.botAI = null;
        this.player = null;
        this.weapon = null;
        this.bullets = [];
        this.mapRoot = null;
        this.mapData = window.gameData?.mapData || {};
        this.matchState = null;
        this.matchDurationSeconds = Number(window.gameData?.matchDurationSeconds || 300);
        this.matchStartedAtMs = window.gameData?.matchStartedAt
            ? Date.parse(window.gameData.matchStartedAt)
            : Date.now();
        this.keys = new Set();
        this.view = { yaw: Math.PI, pitch: -0.18 };
        this.lastHudUpdate = 0;
        this.lastFeedSignature = '';
        this.nextShotAt = 0;
        this.nextPositionSyncAt = 0;
        this.hasShownEndScreen = false;
        this.userId = window.gameData?.userId;
        this.matchId = window.gameData?.matchId;
        this.apiBaseUrl = window.gameData?.apiBaseUrl || '/api/survival-arena';
        this.csrfToken = window.gameData?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.currentWeaponId = null;
        this.worldSize = this.mapData.size || 320;
        this.emergencyVisualsReady = false;
        this.difficulty = window.gameData?.difficulty || this.mapData.difficulty || 'easy';

        // --- Client-side match state ---
        this.localHealth = 100;
        this.localShield = 0;
        this.localAmmo = 30;
        this.localAmmoReserve = 120;
        this.localMagazineSize = 30;
        this.localKills = 0;
        this.localHeadshots = 0;
        this.localKillFeed = [];
        this.isReloading = false;
        this.reloadEndTime = 0;
        this.playerAlive = true;
        this.matchActive = true;
        this.isFiring = false;

        // --- Zone ---
        this.zonePhase = 0;
        this.zoneRadius = 100;
        this.zoneCenter = { x: 0, z: 0 };
        this.zoneShrinkTarget = 100;
        this.zoneNextShrinkAt = 0;
        this.zoneDamagePerSec = 5;
        this.zonePhaseDurations = [60, 50, 40, 30];
        this.zoneDamages = [5, 10, 20, 50];
        this.zoneShrinkAmounts = [15, 15, 15, 15];

        // --- Damage indicator ---
        this.lastDamageTime = 0;
        this.damageFlashAlpha = 0;

        // --- Bot count from server ---
        this.botCount = Number(window.gameData?.botCount ?? this.mapData.bot_count ?? 3);
    }

    async init() {
        window.__survivalArenaBoot.started = true;

        if (!this.createScene()) {
            this.showRendererError();
            return;
        }

        try {
            this.bindInput();
            this.bindResize();
            this.bindPointerLock();

            this.updateLoading('Loading City Pack 8', 20);
            await this.loadWorld();
            this.ensureEmergencyVisuals();

            this.updateLoading('Spawning player', 45);
            await this.createPlayer();

            this.updateLoading('Preparing weapon', 60);
            await this.createWeapon();
            await this.initializeWeapons();

            this.updateLoading('Spawning bots', 75);
            this.initBotAI();
            await this.fetchMatchState();
        } catch (error) {
            console.error('[SurvivalArena] city bootstrap failed', error);

            if (!this.player) {
                await this.createPlayer();
            }
            if (!this.botAI) {
                this.initBotAI();
            }
            if (!this.mapRoot) {
                this.buildFallbackWorld();
            }
            this.ensureEmergencyVisuals(true);

            this.pushNotification('Loaded in fallback mode');
        }

        // Ensure bots exist client-side
        this.ensureLocalBots();

        this.updateLoading('Finalizing HUD', 96);
        this.finishLoading();
        window.__survivalArenaBoot.finished = true;
        this.startNetworkLoops();
        this.initZone();
        this.animate();
    }

    /* ===================================================================
     *  Scene Setup
     * =================================================================== */

    createScene() {
        const container = document.getElementById('game-container');
        if (!container || !window.WebGLRenderingContext) {
            return false;
        }

        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0b1224);
        this.scene.fog = new THREE.Fog(0x0b1224, 45, 280);

        this.camera = new THREE.PerspectiveCamera(72, window.innerWidth / window.innerHeight, 0.25, 1000);
        this.camera.position.set(10, 2.2, 18);

        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.05;

        container.innerHTML = '';
        container.appendChild(this.renderer.domElement);
        return true;
    }

    /* ===================================================================
     *  World Loading
     * =================================================================== */

    async loadWorld() {
        this.mapLoader = new CityMapLoader(this.scene);
        const candidates = [
            this.mapData.map_asset,
            '/assets/models/maps/CityPack8.glb',
            '/assets/models/city_pack_8.glb',
            '/assets/models/CityPack8.glb',
        ].filter(Boolean);

        let loaded = false;
        let lastError = null;

        for (const mapUrl of [...new Set(candidates)]) {
            try {
                const world = await this.mapLoader.load(mapUrl, this.mapData);
                this.mapRoot = world.root;
                const guaranteedBoxes = this.buildGuaranteedCollisionBoxes();
                this.collisionService.setBoxes([...(world.collisionBoxes || []), ...guaranteedBoxes]);
                this.collisionService.setBlockingMeshes(world.blockingMeshes || []);
                loaded = true;

                if ((world.meshCount ?? 0) > 0) {
                    this.updateLoading(`City map loaded (${world.meshCount} meshes)`, 32);
                }
                break;
            } catch (error) {
                lastError = error;
                console.warn(`[SurvivalArena] City map candidate failed: ${mapUrl}`, error);
            }
        }

        if (!loaded) {
            console.warn('[SurvivalArena] City map load failed, using fallback city blocks', lastError);
            this.buildFallbackWorld();
            this.updateLoading('City map unavailable, running fallback world', 32);
        }
    }

    buildGuaranteedCollisionBoxes() {
        const source = this.mapData.collision_boxes || this.mapData.obstacles || [];
        return source.map((item) => new THREE.Box3(
            new THREE.Vector3(
                (item.x ?? 0) - ((item.width ?? 0) / 2),
                item.y ?? 0,
                (item.z ?? 0) - ((item.depth ?? 0) / 2)
            ),
            new THREE.Vector3(
                (item.x ?? 0) + ((item.width ?? 0) / 2),
                (item.y ?? 0) + (item.height ?? 10),
                (item.z ?? 0) + ((item.depth ?? 0) / 2)
            )
        ));
    }

    buildFallbackWorld() {
        const ambient = new THREE.AmbientLight(0xdbeafe, 0.55);
        const hemi = new THREE.HemisphereLight(0x93c5fd, 0x0f172a, 0.9);
        const sun = new THREE.DirectionalLight(0xffffff, 1.1);
        sun.position.set(70, 130, 60);
        sun.castShadow = true;
        this.scene.add(ambient, hemi, sun);

        const ground = new THREE.Mesh(
            new THREE.PlaneGeometry(360, 360, 1, 1),
            new THREE.MeshStandardMaterial({ color: 0x475569, roughness: 1, metalness: 0 })
        );
        ground.rotation.x = -Math.PI / 2;
        ground.receiveShadow = true;
        this.scene.add(ground);

        const boxes = (this.mapData.collision_boxes || []).map((item) => new THREE.Box3(
            new THREE.Vector3(item.x - (item.width / 2), item.y ?? 0, item.z - (item.depth / 2)),
            new THREE.Vector3(item.x + (item.width / 2), (item.y ?? 0) + (item.height ?? 12), item.z + (item.depth / 2))
        ));

        for (const box of boxes) {
            const size = new THREE.Vector3();
            const center = new THREE.Vector3();
            box.getSize(size);
            box.getCenter(center);

            const building = new THREE.Mesh(
                new THREE.BoxGeometry(size.x, size.y, size.z),
                new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.95 })
            );
            building.position.copy(center);
            building.castShadow = true;
            building.receiveShadow = true;
            this.scene.add(building);
        }

        this.collisionService.setBoxes(boxes);
    }

    countSceneMeshes() {
        let count = 0;
        this.scene?.traverse((child) => {
            if (child instanceof THREE.Mesh) {
                count += 1;
            }
        });
        return count;
    }

    ensureEmergencyVisuals(force = false) {
        if (this.emergencyVisualsReady && !force) {
            return;
        }

        const meshCount = this.countSceneMeshes();
        if (!force && meshCount >= 8) {
            return;
        }

        const ground = new THREE.Mesh(
            new THREE.PlaneGeometry(420, 420, 1, 1),
            new THREE.MeshStandardMaterial({ color: 0x64748b, roughness: 0.95, metalness: 0.02 })
        );
        ground.rotation.x = -Math.PI / 2;
        ground.position.y = -0.02;
        ground.receiveShadow = true;
        this.scene.add(ground);

        const grid = new THREE.GridHelper(360, 36, 0x22d3ee, 0x334155);
        grid.position.y = 0.02;
        this.scene.add(grid);

        const fallbackBoxes = [];
        const blocks = [
            [-36, -22, 20, 26, 16],
            [28, -18, 24, 30, 20],
            [22, 32, 18, 24, 18],
            [-24, 36, 20, 28, 20],
            [0, -42, 16, 22, 14],
        ];

        for (const [x, z, w, h, d] of blocks) {
            const building = new THREE.Mesh(
                new THREE.BoxGeometry(w, h, d),
                new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.9, metalness: 0.05 })
            );
            building.position.set(x, h / 2, z);
            building.castShadow = true;
            building.receiveShadow = true;
            this.scene.add(building);

            fallbackBoxes.push(new THREE.Box3(
                new THREE.Vector3(x - (w / 2), 0, z - (d / 2)),
                new THREE.Vector3(x + (w / 2), h, z + (d / 2))
            ));
        }

        if ((this.collisionService.boxes || []).length === 0) {
            this.collisionService.setBoxes(fallbackBoxes);
        }

        this.emergencyVisualsReady = true;
    }

    /* ===================================================================
     *  Player
     * =================================================================== */

    async createPlayer() {
        this.player = new Player();
        const swatFile = 's.w.a.t._operator.glb';
        const baseAssetUrl = (window.gameData?.assetBaseUrl || '').replace(/\/$/, '');
        const bundleDerivedUrl = new URL(`../../../assets/models/${swatFile}`, import.meta.url).toString();
        const playerModelLoaded = await this.player.loadModel([
            bundleDerivedUrl,
            baseAssetUrl ? `${baseAssetUrl}/assets/models/${swatFile}` : null,
            window.gameData?.playerAsset,
            this.mapData.player_asset,
            '/assets/models/s.w.a.t._operator.glb',
            'assets/models/s.w.a.t._operator.glb',
            '/public/assets/models/s.w.a.t._operator.glb',
        ]);
        if (!playerModelLoaded) {
            this.pushNotification('Player GLB unavailable - using fallback mesh');
        }
        const spawn = await this.resolvePlayerSpawn();
        this.player.setPosition(spawn);
        this.player.position.y = Math.max(0.05, spawn.y ?? 0.05);
        this.scene.add(this.player.group);

        this.view.yaw = this.findDefaultFacing(spawn);
        this.camera.position.set(spawn.x, spawn.y + 1.4, spawn.z + 6.2);
        this.camera.lookAt(spawn.x, spawn.y + 1.2, spawn.z);
    }

    async resolvePlayerSpawn() {
        const candidates = [
            this.mapData.spawn_points?.player,
            ...(this.mapData.road_lanes || []),
            { x: 10, z: 20 },
            { x: 18, z: 4 },
            { x: -12, z: 18 },
            { x: 26, z: -6 },
        ].filter(Boolean);

        if (this.mapLoader?.findGroundSpawn) {
            return this.mapLoader.findGroundSpawn(candidates, {
                offsetY: 0.95,
                maxGroundHeight: 3,
            });
        }

        return { x: 10, y: 0.95, z: 20 };
    }

    findDefaultFacing(spawn) {
        const delta = new THREE.Vector3(0, 0, 0).sub(new THREE.Vector3(spawn.x, 0, spawn.z));

        if (delta.lengthSq() === 0) {
            return Math.PI;
        }

        return Math.atan2(delta.x, delta.z);
    }

    /* ===================================================================
     *  Weapon
     * =================================================================== */

    async createWeapon() {
        this.weapon = new Weapon(this.scene);
        await this.weapon.load('/assets/models/low_poly_aek-971.glb');
        this.weapon.attachTo(this.player.group);
    }

    /* ===================================================================
     *  Bot AI Initialization
     * =================================================================== */

    initBotAI() {
        this.botAI = new BotAI(this.scene, {
            difficulty: this.difficulty,
            worldSize: this.worldSize,
        });
        this.botAI.playerRef = this.player;

        // Callback: bot shoots player
        this.botAI.onBotShootPlayer = (botKey, damage, headshot) => {
            if (!this.playerAlive || this.hasShownEndScreen) return;
            this.applyDamageToPlayer(damage, headshot);
        };

        // Callback: bot died
        this.botAI.onBotDied = (botKey, botName) => {
            this.localKills++;
            this.addKillFeedEntry(
                window.gameData?.userName || 'Player',
                botName,
                false
            );
            this.pushNotification(`Eliminated ${botName}`);
            this.playHitSound();
            this.checkWinCondition();
        };
    }

    ensureLocalBots() {
        if (!this.botAI) return;

        const alive = this.botAI.getAliveCount();
        if (alive >= this.botCount) return;

        const spawns = this.mapData.spawn_points?.bots || [];
        this.botAI.spawnLocalBots(this.botCount, spawns);
        this.pushNotification(`${this.botCount} enemies deployed`);
    }

    /* ===================================================================
     *  Input
     * =================================================================== */

    bindInput() {
        window.addEventListener('keydown', (event) => {
            this.keys.add(event.code);
            if (event.code === 'KeyR') {
                this.triggerReload();
            }
        });

        window.addEventListener('keyup', (event) => {
            this.keys.delete(event.code);
        });

        window.addEventListener('mousedown', (event) => {
            if (event.button === 0) {
                this.isFiring = true;
                if (document.pointerLockElement === this.renderer?.domElement) {
                    this.fireWeapon();
                }
            }
        });

        window.addEventListener('mouseup', (event) => {
            if (event.button === 0) {
                this.isFiring = false;
            }
        });

        window.addEventListener('mousemove', (event) => {
            if (document.pointerLockElement !== this.renderer.domElement) {
                return;
            }

            const sensitivity = 0.0022;
            this.view.yaw -= event.movementX * sensitivity;
            this.view.pitch -= event.movementY * sensitivity;
            this.view.pitch = THREE.MathUtils.clamp(this.view.pitch, -1.2, 0.8);
        });
    }

    bindPointerLock() {
        const canvas = this.renderer?.domElement;
        if (!canvas) {
            return;
        }

        canvas.addEventListener('click', () => {
            if (document.pointerLockElement !== canvas) {
                canvas.requestPointerLock();
            }
        });
    }

    bindResize() {
        window.addEventListener('resize', () => {
            if (!this.camera || !this.renderer) {
                return;
            }

            this.camera.aspect = window.innerWidth / window.innerHeight;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(window.innerWidth, window.innerHeight);
        });
    }

    getInputState() {
        return {
            forward: this.keys.has('KeyW'),
            backward: this.keys.has('KeyS'),
            left: this.keys.has('KeyA'),
            right: this.keys.has('KeyD'),
            sprint: this.keys.has('ShiftLeft'),
            jump: this.keys.has('Space'),
        };
    }

    /* ===================================================================
     *  Camera
     * =================================================================== */

    updateCamera(delta) {
        if (!this.player || !this.camera) {
            return;
        }

        const playerPosition = this.player.position.clone();
        const cameraAnchor = playerPosition.clone();
        cameraAnchor.y += 1.2;

        const behind = new THREE.Vector3(
            Math.sin(this.view.yaw) * -1,
            Math.sin(this.view.pitch),
            Math.cos(this.view.yaw) * -1
        ).normalize();

        const cameraOffset = behind.clone().multiplyScalar(8.5);
        cameraOffset.y += 2.2;

        const desired = playerPosition.clone().add(cameraOffset);

        // Keep camera outside blocking geometry to prevent inside-building view clipping.
        const rayDirection = desired.clone().sub(cameraAnchor);
        const desiredDistance = rayDirection.length();
        let resolved = desired;

        if (desiredDistance > 0.001) {
            rayDirection.normalize();
            const hitDistance = this.collisionService.raycastBlockingDistance(
                cameraAnchor,
                rayDirection,
                desiredDistance
            );

            if (hitDistance < desiredDistance) {
                const safeDistance = Math.max(1.4, hitDistance - 0.35);
                resolved = cameraAnchor.clone().add(rayDirection.multiplyScalar(safeDistance));
            }
        }

        this.camera.position.lerp(resolved, 0.18);
        this.camera.lookAt(cameraAnchor.x, cameraAnchor.y, cameraAnchor.z);
        this.player.rotation.y = this.view.yaw;
    }

    /* ===================================================================
     *  Combat: Player shooting
     * =================================================================== */

    fireWeapon() {
        if (!this.playerAlive || this.hasShownEndScreen) return;
        if (this.isReloading) return;

        const now = performance.now();
        if (now < this.nextShotAt) return;
        this.nextShotAt = now + 160;

        // Check ammo
        if (this.localAmmo <= 0) {
            this.triggerReload();
            return;
        }

        this.localAmmo--;

        // Compute aim direction from player's yaw/pitch (forward facing direction)
        const direction = new THREE.Vector3(
            Math.sin(this.view.yaw) * Math.cos(this.view.pitch),
            -Math.sin(this.view.pitch),
            Math.cos(this.view.yaw) * Math.cos(this.view.pitch)
        ).normalize();

        if (this.weapon) {
            this.weapon.flashMuzzle(this.scene);
        }
        this.playShootSound();

        // Bullet origin: from player's chest height
        const bulletOrigin = this.player.position.clone().add(new THREE.Vector3(0, 1.2, 0));

        const bullet = new Bullet(
            this.scene,
            this.weapon ? this.weapon.getMuzzleWorldPosition() : bulletOrigin,
            direction,
            this.collisionService,
            { maxDistance: 120, speed: 220 }
        );
        this.bullets.push(bullet);

        // LOCAL hit detection against bots
        if (this.botAI) {
            const hit = this.botAI.raycastBots(
                bulletOrigin,
                direction,
                120
            );
            if (hit) {
                const baseDmg = 18 + Math.floor(Math.random() * 8);
                const dmg = hit.headshot ? baseDmg * 2 : baseDmg;
                const result = this.botAI.damageBot(hit.key, dmg, hit.headshot);
                if (result) {
                    if (result.killed) {
                        if (hit.headshot) this.localHeadshots++;
                    }
                    this.showHitMarker(hit.headshot);
                }
            }
        }

        // Also try server-side shoot (fire-and-forget)
        this.serverShoot(direction);

        // Auto-reload when empty
        if (this.localAmmo <= 0 && this.localAmmoReserve > 0) {
            setTimeout(() => this.triggerReload(), 400);
        }
    }

    async serverShoot(direction) {
        if (!this.matchId || !this.currentWeaponId) return;
        try {
            await fetch(`${this.apiBaseUrl}/matches/${this.matchId}/shoot`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    direction: {
                        x: Number(direction.x.toFixed(4)),
                        y: Number(direction.y.toFixed(4)),
                        z: Number(direction.z.toFixed(4)),
                    },
                    weapon_id: this.currentWeaponId,
                }),
            });
        } catch (_) { /* server shoot is best-effort */ }
    }

    showHitMarker(headshot) {
        const crosshair = document.querySelector('.crosshair');
        if (!crosshair) return;
        crosshair.classList.add(headshot ? 'hit-headshot' : 'hit-normal');
        setTimeout(() => {
            crosshair.classList.remove('hit-normal', 'hit-headshot');
        }, 200);
    }

    /* ===================================================================
     *  Combat: Player taking damage
     * =================================================================== */

    applyDamageToPlayer(damage, headshot) {
        if (!this.playerAlive) return;

        // Apply to shield first
        if (this.localShield > 0) {
            const shieldAbsorbed = Math.min(this.localShield, damage);
            this.localShield -= shieldAbsorbed;
            damage -= shieldAbsorbed;
        }

        this.localHealth = Math.max(0, this.localHealth - damage);
        this.lastDamageTime = performance.now();
        this.damageFlashAlpha = 0.5;

        // Show damage direction indicator
        this.showDamageIndicator();

        if (this.localHealth <= 0) {
            this.playerAlive = false;
            this.showDeath();
        }
    }

    showDamageIndicator() {
        const container = document.getElementById('damage-indicators');
        if (!container) return;

        const indicator = document.createElement('div');
        indicator.className = 'damage-flash';
        container.appendChild(indicator);
        setTimeout(() => indicator.remove(), 500);
    }

    /* ===================================================================
     *  Reload
     * =================================================================== */

    triggerReload() {
        if (this.isReloading) return;
        if (this.localAmmoReserve <= 0) return;
        if (this.localAmmo >= this.localMagazineSize) return;

        this.isReloading = true;
        this.reloadEndTime = performance.now() + 1800;

        const indicator = document.getElementById('reload-indicator');
        if (indicator) {
            indicator.classList.add('active');
        }

        setTimeout(() => {
            const needed = this.localMagazineSize - this.localAmmo;
            const toLoad = Math.min(needed, this.localAmmoReserve);
            this.localAmmo += toLoad;
            this.localAmmoReserve -= toLoad;
            this.isReloading = false;

            if (indicator) {
                indicator.classList.remove('active');
            }
        }, 1800);
    }

    /* ===================================================================
     *  Zone / Safe Zone
     * =================================================================== */

    initZone() {
        this.zonePhase = 0;
        this.zoneRadius = 100;
        this.zoneShrinkTarget = 100;
        this.zoneNextShrinkAt = performance.now() + this.zonePhaseDurations[0] * 1000;
        this.zoneDamagePerSec = this.zoneDamages[0];
    }

    updateZone(delta) {
        const now = performance.now();

        // Check if time to shrink
        if (now >= this.zoneNextShrinkAt && this.zonePhase < this.zonePhaseDurations.length) {
            this.zonePhase++;
            if (this.zonePhase < this.zonePhaseDurations.length) {
                this.zoneShrinkTarget = Math.max(10, this.zoneRadius - this.zoneShrinkAmounts[this.zonePhase]);
                this.zoneNextShrinkAt = now + this.zonePhaseDurations[this.zonePhase] * 1000;
                this.zoneDamagePerSec = this.zoneDamages[this.zonePhase] || 50;
                this.pushNotification(`Zone Phase ${this.zonePhase + 1} — Zone shrinking!`);
            }
        }

        // Smoothly shrink zone
        if (this.zoneRadius > this.zoneShrinkTarget) {
            this.zoneRadius = Math.max(this.zoneShrinkTarget, this.zoneRadius - delta * 3);
        }

        // Check if player outside zone
        if (this.playerAlive && this.player) {
            const dx = this.player.position.x - this.zoneCenter.x;
            const dz = this.player.position.z - this.zoneCenter.z;
            const distFromCenter = Math.sqrt(dx * dx + dz * dz);
            if (distFromCenter > this.zoneRadius) {
                this.applyDamageToPlayer(Math.round(this.zoneDamagePerSec * delta), false);
            }
        }
    }

    /* ===================================================================
     *  Kill feed
     * =================================================================== */

    addKillFeedEntry(killerName, victimName, headshot) {
        this.localKillFeed.unshift({
            killer_name: killerName,
            victim_name: victimName,
            headshot: headshot,
            created_at: new Date().toISOString(),
        });
        if (this.localKillFeed.length > 6) {
            this.localKillFeed = this.localKillFeed.slice(0, 6);
        }
    }

    /* ===================================================================
     *  Win / Loss
     * =================================================================== */

    checkWinCondition() {
        if (this.hasShownEndScreen) return;
        if (!this.botAI) return;

        const alive = this.botAI.getAliveCount();
        if (alive <= 0) {
            this.showVictory();
        }
    }

    /* ===================================================================
     *  Server sync (optional / best-effort)
     * =================================================================== */

    async fetchMatchState() {
        if (!this.matchId) {
            return;
        }

        try {
            const response = await this.fetchWithTimeout(`${this.apiBaseUrl}/matches/${this.matchId}/state`, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            }, 3500);

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.matchState = data;
            this.syncServerState(data);
        } catch (error) {
            console.warn('[SurvivalArena] state sync failed — running locally', error);
        }
    }

    syncServerState(data) {
        // Sync bots from server if available
        const botStates = (data.players || []).filter((state) => state.is_bot);
        if (botStates.length > 0) {
            this.botAI?.sync(botStates);
        }

        // Server kill feed
        if (data.kill_feed && data.kill_feed.length > 0) {
            for (const entry of data.kill_feed) {
                const exists = this.localKillFeed.some(
                    e => e.killer_name === entry.killer_name &&
                         e.victim_name === entry.victim_name &&
                         e.created_at === entry.created_at
                );
                if (!exists) {
                    this.localKillFeed.unshift(entry);
                }
            }
            this.localKillFeed = this.localKillFeed.slice(0, 6);
        }

        // Self state from server (health, ammo) — only override if server is more authoritative
        const selfState = (data.players || []).find(s => !s.is_bot && s.player_id === this.userId);
        if (selfState && selfState.health !== undefined) {
            // Use the lower health (more damaged) as authoritative
            if (selfState.health < this.localHealth) {
                this.localHealth = selfState.health;
            }
        }

        // Match ended by server
        if (data.status === 'finished' && !this.hasShownEndScreen) {
            this.hasShownEndScreen = true;
            if ((data.player_summary?.placement ?? 99) === 1) {
                this.showVictory();
            } else {
                this.showDeath();
            }
        }
    }

    startNetworkLoops() {
        this.pollHandle = window.setInterval(() => this.fetchMatchState(), 2000);
    }

    async initializeWeapons() {
        try {
            const response = await this.fetchWithTimeout(`${this.apiBaseUrl}/weapons`, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            }, 3500);

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            this.currentWeaponId = data?.weapons?.[0]?.id ?? null;

            // Use server weapon stats
            const w = data?.weapons?.[0];
            if (w) {
                this.localMagazineSize = w.magazineSize || 30;
                this.localAmmo = this.localMagazineSize;
            }
        } catch (error) {
            console.warn('[SurvivalArena] weapon init failed — using defaults', error);
        }
    }

    async sendPositionUpdate() {
        if (!this.matchId || !this.player) {
            return;
        }

        const now = performance.now();
        if (now < this.nextPositionSyncAt) {
            return;
        }
        this.nextPositionSyncAt = now + 500; // less frequent to avoid spam

        const velocity = this.player.velocity || new THREE.Vector3();

        try {
            await fetch(`${this.apiBaseUrl}/matches/${this.matchId}/position`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    position: {
                        x: Number(this.player.position.x.toFixed(3)),
                        y: Number(this.player.position.y.toFixed(3)),
                        z: Number(this.player.position.z.toFixed(3)),
                    },
                    rotation: {
                        x: 0,
                        y: Number(this.player.rotation.y.toFixed(3)),
                        z: 0,
                    },
                    velocity: {
                        x: Number(velocity.x.toFixed(3)),
                        y: Number(velocity.y.toFixed(3)),
                        z: Number(velocity.z.toFixed(3)),
                    },
                    is_sprinting: this.keys.has('ShiftLeft'),
                    is_crouching: this.keys.has('ControlLeft'),
                }),
            });
        } catch (_) { /* best-effort */ }
    }

    /* ===================================================================
     *  Audio
     * =================================================================== */

    playShootSound() {
        this.playTone(420, 0.04, 'square');
    }

    playHitSound() {
        this.playTone(140, 0.08, 'sawtooth');
    }

    playTone(frequency, duration, type = 'sine') {
        const AudioContextCtor = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextCtor) {
            return;
        }

        if (!this.audioContext) {
            this.audioContext = new AudioContextCtor();
        }

        const oscillator = this.audioContext.createOscillator();
        const gain = this.audioContext.createGain();
        oscillator.type = type;
        oscillator.frequency.value = frequency;
        gain.gain.value = 0.04;
        oscillator.connect(gain);
        gain.connect(this.audioContext.destination);
        oscillator.start();
        oscillator.stop(this.audioContext.currentTime + duration);
    }

    /* ===================================================================
     *  HUD
     * =================================================================== */

    updateHud(elapsed) {
        if (elapsed - this.lastHudUpdate < 0.1) {
            return;
        }
        this.lastHudUpdate = elapsed;

        const nowMs = Date.now();
        const elapsedSeconds = Math.max(0, Math.floor((nowMs - this.matchStartedAtMs) / 1000));
        const matchRemaining = Math.max(0, this.matchDurationSeconds - elapsedSeconds);

        // Zone timer
        const zoneRemaining = Math.max(0, Math.ceil((this.zoneNextShrinkAt - performance.now()) / 1000));
        const zonePhaseDuration = this.zonePhase < this.zonePhaseDurations.length
            ? this.zonePhaseDurations[this.zonePhase] : 60;
        const zoneProgress = 1 - (zoneRemaining / zonePhaseDuration);

        this.setText('zone-timer', `${zoneRemaining}s`);
        this.setText('match-timer', this.formatDuration(matchRemaining));
        this.setWidth('pollution-fill', `${Math.max(0, Math.min(zoneProgress * 100, 100))}%`);

        // Health / Shield
        this.setWidth('health-fill', `${Math.max(0, Math.min(this.localHealth, 100))}%`);
        this.setWidth('shield-fill', `${Math.max(0, Math.min(this.localShield, 100))}%`);
        this.setText('health-value', Math.round(this.localHealth));
        this.setText('shield-value', Math.round(this.localShield));

        // Ammo
        this.setText('ammo-current', this.localAmmo);
        this.setText('ammo-reserve', this.localAmmoReserve);

        // Kills
        this.setText('kills-count', this.localKills);

        // Alive count
        const botAlive = this.botAI ? this.botAI.getAliveCount() : 0;
        this.setText('alive-count', botAlive + (this.playerAlive ? 1 : 0));

        // Weapon name
        this.setText('weapon-name', 'AEK-971');

        // Kill feed
        this.renderKillFeed();

        // Minimap
        this.drawMinimap();

        // Match time expired
        if (matchRemaining <= 0 && !this.hasShownEndScreen) {
            this.hasShownEndScreen = true;
            if (this.playerAlive) {
                this.showVictory();
            } else {
                this.showDeath();
            }
            this.pushNotification('Match time reached 5:00');
        }

        // Damage flash
        this.updateDamageFlash();
    }

    updateDamageFlash() {
        if (this.damageFlashAlpha > 0) {
            this.damageFlashAlpha = Math.max(0, this.damageFlashAlpha - 0.008);
            const container = document.getElementById('damage-indicators');
            if (container) {
                container.style.background = `radial-gradient(ellipse at center, transparent 50%, rgba(239,68,68,${this.damageFlashAlpha}) 100%)`;
            }
        }
    }

    formatDuration(totalSeconds) {
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes}:${String(seconds).padStart(2, '0')}`;
    }

    async fetchWithTimeout(url, options = {}, timeoutMs = 3500) {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), timeoutMs);

        try {
            return await fetch(url, {
                ...options,
                signal: controller.signal,
            });
        } finally {
            window.clearTimeout(timeout);
        }
    }

    renderKillFeed() {
        const feedRoot = document.getElementById('kill-feed');
        if (!feedRoot) return;

        const feed = this.localKillFeed.slice(0, 4);
        const signature = feed.map(e => `${e.killer_name}:${e.victim_name}:${e.created_at}`).join('|');
        if (signature === this.lastFeedSignature) return;

        this.lastFeedSignature = signature;
        feedRoot.innerHTML = feed.map((entry) => {
            const hs = entry.headshot ? ' <span class="hs-badge">[HS]</span>' : '';
            return `<div class="kill-entry">${entry.killer_name} <span class="kill-action">eliminated</span> ${entry.victim_name}${hs}</div>`;
        }).join('');
    }

    drawMinimap() {
        const canvas = document.getElementById('minimap');
        if (!(canvas instanceof HTMLCanvasElement) || !this.player) {
            return;
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const size = canvas.width;
        const center = size / 2;
        ctx.clearRect(0, 0, size, size);
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(0, 0, size, size);
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.35)';
        ctx.lineWidth = 2;
        ctx.strokeRect(1, 1, size - 2, size - 2);

        // Zone circle
        const zoneScale = (size * 0.42) / (this.worldSize * 0.5);
        ctx.strokeStyle = 'rgba(163, 230, 53, 0.8)';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.arc(
            center + this.zoneCenter.x * zoneScale,
            center + this.zoneCenter.z * zoneScale,
            this.zoneRadius * zoneScale,
            0, Math.PI * 2
        );
        ctx.stroke();

        const scale = size * 0.42;

        // Player dot
        ctx.fillStyle = '#22d3ee';
        ctx.beginPath();
        ctx.arc(
            center + (this.player.position.x / this.worldSize) * scale,
            center + (this.player.position.z / this.worldSize) * scale,
            4, 0, Math.PI * 2
        );
        ctx.fill();

        // Bot dots
        if (this.botAI) {
            for (const state of this.botAI.getAllBotStates()) {
                if (!state.alive) continue;
                ctx.fillStyle = '#fb923c';
                ctx.beginPath();
                ctx.arc(
                    center + (state.position.x / this.worldSize) * scale,
                    center + (state.position.z / this.worldSize) * scale,
                    3, 0, Math.PI * 2
                );
                ctx.fill();
            }
        }

        // Player facing direction
        ctx.strokeStyle = '#22d3ee';
        ctx.lineWidth = 1.5;
        const px = center + (this.player.position.x / this.worldSize) * scale;
        const pz = center + (this.player.position.z / this.worldSize) * scale;
        ctx.beginPath();
        ctx.moveTo(px, pz);
        ctx.lineTo(
            px + Math.sin(this.view.yaw) * 12,
            pz + Math.cos(this.view.yaw) * 12
        );
        ctx.stroke();
    }

    /* ===================================================================
     *  UI Helpers
     * =================================================================== */

    setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = String(value);
        }
    }

    setWidth(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.style.width = value;
        }
    }

    showDeath() {
        if (this.hasShownEndScreen) return;
        const screen = document.getElementById('death-screen');
        if (screen) {
            screen.classList.remove('hidden');
        }
        this.hasShownEndScreen = true;
        this.playerAlive = false;
        this.matchActive = false;
        this.applyEndStats();
    }

    showVictory() {
        if (this.hasShownEndScreen) return;
        const screen = document.getElementById('victory-screen');
        if (screen) {
            screen.classList.remove('hidden');
        }
        this.hasShownEndScreen = true;
        this.matchActive = false;
        this.applyEndStats();
        this.playVictorySound();
    }

    playVictorySound() {
        try {
            [523, 659, 784, 1047].forEach((freq, i) => {
                setTimeout(() => this.playTone(freq, 0.15, 'sine'), i * 120);
            });
        } catch (_) {}
    }

    applyEndStats() {
        const nowMs = Date.now();
        const survivalSeconds = Math.floor((nowMs - this.matchStartedAtMs) / 1000);
        const minutes = Math.floor(survivalSeconds / 60);
        const seconds = survivalSeconds % 60;
        const survival = `${minutes}:${String(seconds).padStart(2, '0')}`;

        const placement = this.playerAlive ? '#1' : `#${(this.botAI?.getAliveCount() ?? 0) + 1}`;
        const score = (this.localKills * 10) + (this.localHeadshots * 20) +
                      Math.floor(survivalSeconds / 10) + (this.playerAlive ? 100 : 0);

        this.setText('placement', placement);
        this.setText('final-kills', this.localKills);
        this.setText('survival-time', survival);
        this.setText('win-placement', placement);
        this.setText('win-kills', this.localKills);
        this.setText('win-xp', `Score ${score}`);
    }

    pushNotification(message) {
        const root = document.getElementById('notifications');
        if (!root) {
            return;
        }

        const entry = document.createElement('div');
        entry.className = 'notification-item';
        entry.textContent = message;
        root.appendChild(entry);
        window.setTimeout(() => entry.remove(), 2500);
    }

    /* ===================================================================
     *  Bullets
     * =================================================================== */

    updateBullets(delta) {
        this.bullets = this.bullets.filter((bullet) => {
            bullet.update(delta);
            return !bullet.finished;
        });
    }

    /* ===================================================================
     *  Main Animation Loop
     * =================================================================== */

    animate() {
        const loop = () => {
            const delta = this.clock.getDelta();
            const elapsed = this.clock.elapsedTime;

            if (this.player && this.playerAlive) {
                this.player.update(delta, this.getInputState(), this.collisionService, this.view.yaw);

                // Auto-fire while holding left mouse button
                if (this.isFiring && document.pointerLockElement === this.renderer?.domElement) {
                    this.fireWeapon();
                }
            }

            this.ensureEmergencyVisuals();

            this.updateCamera(delta);

            // Update bot AI with full client-side simulation
            if (this.botAI) {
                this.botAI.playerRef = this.player;
                this.botAI._cameraRef = this.camera;
                this.botAI.update(delta);
            }

            this.updateBullets(delta);
            this.updateHud(elapsed);

            if (this.matchActive) {
                this.updateZone(delta);
            }

            this.sendPositionUpdate();

            if (this.renderer && this.scene && this.camera) {
                this.renderer.render(this.scene, this.camera);
            }

            requestAnimationFrame(loop);
        };

        requestAnimationFrame(loop);
    }

    /* ===================================================================
     *  Loading
     * =================================================================== */

    finishLoading() {
        this.updateLoading('Match initialized — Click to play', 100);
        window.setTimeout(() => {
            document.getElementById('loading-screen')?.classList.add('hidden');
        }, 300);
    }

    updateLoading(text, progress) {
        const textEl = document.getElementById('loading-text');
        const fillEl = document.getElementById('loading-fill');
        if (textEl) {
            textEl.textContent = text;
        }
        if (fillEl) {
            fillEl.style.width = `${progress}%`;
        }
    }

    showRendererError() {
        const container = document.getElementById('game-container');
        if (container) {
            container.innerHTML = '<div class="scene-surface">WebGL is unavailable in this browser/device.</div>';
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new SurvivalArenaCityGame().init();
    });
} else {
    new SurvivalArenaCityGame().init();
}
