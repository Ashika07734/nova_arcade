// public/games/survival-arena-3d/js/main.js
import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { BotAI } from './services/BotAI.js';

class SurvivalArenaBootstrap {
    constructor() {
        this.zoneDuration = 60;
        this.keys = new Set();

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.clock = new THREE.Clock();
        this.player = null;
        this.zoneRing = null;
        this.lootCrates = [];
        this.lastHudUpdate = 0;
        this.worldRadius = 90;

        this.loader = new GLTFLoader();
        this.assets = {
            environment: '/assets/models/low_poly_forest.glb',
            treeA: '/assets/models/low_poly_trees_free.glb',
            treeC: '/assets/models/low_poly_tree_scene_free.glb',
            treeB: '/assets/models/sapling.glb',
            propFence: '/assets/models/fench.glb',
            propHouse: '/assets/models/seven_dwarfs_cottage.glb',
            propHouseAlt: '/assets/models/House 4.glb',
            propWell: '/assets/models/center-well.glb',
            propChair: '/assets/models/chair2.glb',
            propLamp: '/assets/models/street light.glb',
            propWindmill: '/assets/models/windmill_game_ready.glb',
            weaponPrimary: '/assets/models/low-poly_aek-971.glb',
            weaponSidearm: '/assets/models/sig_sauer_p226_x-_five_low-poly.glb',
        };

        this.assetCache = new Map();
        this.botAI = null;
        this.matchState = null;

        this.gameData = window.gameData || {};
        this.matchId = this.gameData.matchId;
        this.userId = this.gameData.userId;
        this.apiBaseUrl = this.gameData.apiBaseUrl || '/api/survival-arena';
        this.csrfToken = this.gameData.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.currentWeaponId = null;
        this.nextShotAt = 0;
        this.nextPositionSyncAt = 0;
        this.pollHandle = null;
        this.lastFeedSignature = '';
        this.hasShownEndScreen = false;
    }

    async init() {
        this.updateLoading('Booting renderer', 15);
        const created = this.createScene();
        if (!created) {
            this.showRendererError();
            return;
        }

        this.updateLoading('Building arena', 45);
        this.buildArena();

        this.updateLoading('Loading uploaded assets', 65);
        await this.loadUploadedAssets();

        this.botAI = new BotAI(this.scene);

        this.updateLoading('Binding controls', 78);
        this.bindUI();
        this.bindResize();

        this.updateLoading('Syncing server state', 88);
        await this.initializeWeapons();
        await this.fetchMatchState();

        this.updateLoading('Finalizing HUD', 96);
        this.finishLoading();
        this.startNetworkLoops();
        this.animate();
    }

    createScene() {
        const container = document.getElementById('game-container');
        if (!container) return false;

        if (!window.WebGLRenderingContext) {
            return false;
        }

        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x0b1224);
        this.scene.fog = new THREE.Fog(0x0b1224, 28, 160);

        this.camera = new THREE.PerspectiveCamera(70, window.innerWidth / window.innerHeight, 0.1, 500);
        this.camera.position.set(0, 2.2, 7);

        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        this.renderer.setSize(window.innerWidth, window.innerHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.1;
        this.renderer.shadowMap.enabled = true;

        container.innerHTML = '';
        container.appendChild(this.renderer.domElement);
        return true;
    }

    buildArena() {
        const hemi = new THREE.HemisphereLight(0x7dd3fc, 0x0f172a, 0.85);
        this.scene.add(hemi);

        const sun = new THREE.DirectionalLight(0xa7f3d0, 0.9);
        sun.position.set(12, 18, 6);
        sun.castShadow = true;
        sun.shadow.mapSize.width = 1024;
        sun.shadow.mapSize.height = 1024;
        this.scene.add(sun);

        const ground = new THREE.Mesh(
            new THREE.PlaneGeometry(180, 180, 12, 12),
            new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.9, metalness: 0.05 })
        );
        ground.rotation.x = -Math.PI / 2;
        ground.receiveShadow = true;
        this.scene.add(ground);

        const ringGeometry = new THREE.RingGeometry(22.5, 23.2, 96);
        const ringMaterial = new THREE.MeshBasicMaterial({
            color: 0xa3e635,
            transparent: true,
            opacity: 0.75,
            side: THREE.DoubleSide,
        });
        this.zoneRing = new THREE.Mesh(ringGeometry, ringMaterial);
        this.zoneRing.rotation.x = -Math.PI / 2;
        this.zoneRing.position.y = 0.05;
        this.scene.add(this.zoneRing);

        for (let i = 0; i < 14; i += 1) {
            const crate = new THREE.Mesh(
                new THREE.BoxGeometry(1.35, 1.35, 1.35),
                new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.7, metalness: 0.15 })
            );
            crate.position.set((Math.random() - 0.5) * 44, 0.68, (Math.random() - 0.5) * 44);
            crate.castShadow = true;
            this.lootCrates.push(crate);
            this.scene.add(crate);
        }

        this.player = new THREE.Mesh(
            new THREE.CapsuleGeometry(0.4, 1.0, 4, 12),
            new THREE.MeshStandardMaterial({ color: 0x38bdf8, roughness: 0.55, metalness: 0.2 })
        );
        this.player.position.set(0, 1.2, 0);
        this.player.castShadow = true;
        this.scene.add(this.player);
    }

    async loadUploadedAssets() {
        const loadedEnvironment = await this.loadEnvironmentMap();
        if (!loadedEnvironment) {
            this.spawnProceduralTrees();
        }

        await Promise.all([
            this.scatterModel(this.assets.treeA, 18, 0.55, 1.15, 0, 0),
            this.scatterModel(this.assets.treeB, 16, 0.8, 1.5, 0, 0),
            this.scatterModel(this.assets.treeC, 10, 0.7, 1.2, -18, -8),
            this.scatterModel(this.assets.propFence, 16, 0.8, 1.1, 22, 12),
            this.scatterModel(this.assets.propLamp, 8, 0.9, 1.2, 18, 10),
        ]);

        await this.placeVillageCore();
        await this.attachWeaponToPlayer(this.assets.weaponPrimary, { x: 0.24, y: 0.58, z: 0.1 }, 0.9, Math.PI / 2);
    }

    async loadEnvironmentMap() {
        const env = await this.loadModel(this.assets.environment);
        if (!env) return false;

        this.prepareModel(env, true, true);
        env.position.set(0, 0, 0);
        env.scale.setScalar(1.1);
        this.scene.add(env);
        return true;
    }

    spawnProceduralTrees() {
        for (let i = 0; i < 28; i += 1) {
            const trunk = new THREE.Mesh(
                new THREE.CylinderGeometry(0.18, 0.28, 2.2, 10),
                new THREE.MeshStandardMaterial({ color: 0x92400e, roughness: 0.95 })
            );
            const crown = new THREE.Mesh(
                new THREE.ConeGeometry(1.0 + Math.random() * 0.5, 2.2 + Math.random() * 0.8, 8),
                new THREE.MeshStandardMaterial({ color: 0x16a34a, roughness: 0.8 })
            );
            const angle = Math.random() * Math.PI * 2;
            const radius = 12 + Math.random() * 62;
            const x = Math.cos(angle) * radius;
            const z = Math.sin(angle) * radius;

            trunk.position.set(x, 1.1, z);
            crown.position.set(x, 2.9, z);
            trunk.castShadow = true;
            crown.castShadow = true;
            this.scene.add(trunk, crown);
        }
    }

    prepareModel(model, castShadow = true, receiveShadow = true) {
        model.traverse((child) => {
            if (!(child instanceof THREE.Mesh)) return;
            child.castShadow = castShadow;
            child.receiveShadow = receiveShadow;
            if (Array.isArray(child.material)) {
                child.material.forEach((mat) => {
                    if (mat) mat.needsUpdate = true;
                });
            } else if (child.material) {
                child.material.needsUpdate = true;
            }
        });
    }

    async loadModel(url) {
        if (this.assetCache.has(url)) {
            return this.cloneModel(this.assetCache.get(url));
        }

        try {
            const gltf = await new Promise((resolve, reject) => {
                this.loader.load(
                    encodeURI(url),
                    (result) => resolve(result),
                    undefined,
                    (error) => reject(error)
                );
            });

            const base = gltf.scene;
            this.assetCache.set(url, base);
            return this.cloneModel(base);
        } catch (error) {
            console.warn(`[SurvivalArena] Failed to load model: ${url}`, error);
            return null;
        }
    }

    cloneModel(model) {
        return model.clone(true);
    }

    fitModelToHeight(model, targetHeight) {
        const box = new THREE.Box3().setFromObject(model);
        const size = new THREE.Vector3();
        box.getSize(size);
        const height = size.y || 1;
        const scale = targetHeight / height;
        model.scale.multiplyScalar(scale);

        const fittedBox = new THREE.Box3().setFromObject(model);
        const fittedCenter = new THREE.Vector3();
        fittedBox.getCenter(fittedCenter);
        model.position.sub(fittedCenter);
        model.position.y -= fittedBox.min.y;
    }

    randomPointInAnnulus(innerRadius, outerRadius) {
        const angle = Math.random() * Math.PI * 2;
        const radius = innerRadius + Math.random() * (outerRadius - innerRadius);
        return {
            x: Math.cos(angle) * radius,
            z: Math.sin(angle) * radius,
        };
    }

    async scatterModel(url, count, minScale, maxScale, innerRadius = 10, outerRadius = 75) {
        const prototype = await this.loadModel(url);
        if (!prototype) return;

        for (let i = 0; i < count; i += 1) {
            const model = this.cloneModel(prototype);
            this.prepareModel(model, true, true);

            this.fitModelToHeight(model, THREE.MathUtils.randFloat(1.4, 4.5));

            const point = this.randomPointInAnnulus(innerRadius, outerRadius);
            model.position.set(point.x, 0, point.z);
            model.scale.multiplyScalar(THREE.MathUtils.randFloat(minScale, maxScale));
            model.rotation.y = Math.random() * Math.PI * 2;
            this.scene.add(model);
        }
    }

    async placeSingleLandmark(url, position, targetHeight, rotationY = 0) {
        const model = await this.loadModel(url);
        if (!model) return;
        this.prepareModel(model, true, true);
        this.fitModelToHeight(model, targetHeight);
        model.position.set(position.x, position.y, position.z);
        model.rotation.y = rotationY;
        this.scene.add(model);
    }

    async placeVillageCore() {
        const housePositions = [
            { x: 16, y: 0, z: -14, scale: 5.8, rotation: Math.PI * 0.18, url: this.assets.propHouse },
            { x: 24, y: 0, z: -6, scale: 5.2, rotation: -Math.PI * 0.35, url: this.assets.propHouseAlt },
        ];

        for (const house of housePositions) {
            await this.placeSingleLandmark(house.url, { x: house.x, y: house.y, z: house.z }, house.scale, house.rotation);
        }

        await this.placeSingleLandmark(this.assets.propWindmill, { x: 38, y: 0, z: -24 }, 10.5, -Math.PI * 0.15);
        await this.placeSingleLandmark(this.assets.propWell, { x: 20, y: 0, z: -10 }, 2.4, 0);
        await this.placeSingleLandmark(this.assets.propChair, { x: 19, y: 0, z: -8 }, 1.1, Math.PI * 0.4);

        const villageFencePoints = [
            { x: 10, z: -20 },
            { x: 28, z: -20 },
            { x: 32, z: -12 },
            { x: 32, z: -1 },
            { x: 12, z: 2 },
        ];

        for (let i = 0; i < villageFencePoints.length; i += 1) {
            const point = villageFencePoints[i];
            await this.placeSingleLandmark(this.assets.propFence, { x: point.x, y: 0, z: point.z }, 3.2, Math.PI * 0.5 * (i % 2 === 0 ? 1 : 0.5));
        }
    }

    async attachWeaponToPlayer(url, offset, targetHeight, rotationY = 0) {
        const weapon = await this.loadModel(url);
        if (!weapon || !this.player) return;
        this.prepareModel(weapon, true, true);
        this.fitModelToHeight(weapon, targetHeight);
        weapon.position.set(offset.x, offset.y, offset.z);
        weapon.rotation.y = rotationY;
        this.player.add(weapon);
    }

    bindUI() {
        window.addEventListener('keydown', (event) => {
            this.keys.add(event.code);
            if (event.code === 'KeyR') {
                this.triggerReload();
            }
            if (event.code === 'Space') {
                const velocity = this.player.userData.velocity || { x: 0, y: 0, z: 0 };
                if ((this.player.position.y ?? 1) <= 1.05) {
                    velocity.y = 5.0;
                    this.player.userData.velocity = velocity;
                }
            }
        });

        window.addEventListener('keyup', (event) => {
            this.keys.delete(event.code);
        });

        window.addEventListener('mousedown', (event) => {
            if (event.button === 0) {
                this.shoot();
            }
        });
    }

    bindResize() {
        window.addEventListener('resize', () => {
            if (!this.camera || !this.renderer) return;
            this.camera.aspect = window.innerWidth / window.innerHeight;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(window.innerWidth, window.innerHeight);
        });
    }

    handleMovement(delta) {
        if (!this.player || !this.camera) return;

        const speed = this.keys.has('ShiftLeft') ? 8.5 : 5.2;
        const move = new THREE.Vector3();
        if (this.keys.has('KeyW')) move.z -= 1;
        if (this.keys.has('KeyS')) move.z += 1;
        if (this.keys.has('KeyA')) move.x -= 1;
        if (this.keys.has('KeyD')) move.x += 1;

        if (move.lengthSq() > 0) {
            move.normalize().multiplyScalar(speed * delta);
            this.player.position.add(move);
            this.player.position.x = THREE.MathUtils.clamp(this.player.position.x, -80, 80);
            this.player.position.z = THREE.MathUtils.clamp(this.player.position.z, -80, 80);
        }

        const velocity = this.player.userData.velocity || { x: 0, y: 0, z: 0 };
        velocity.y += -9.81 * delta;
        this.player.position.y += velocity.y * delta;
        if (this.player.position.y <= 1) {
            this.player.position.y = 1;
            velocity.y = 0;
        }
        this.player.userData.velocity = velocity;

        const cameraTarget = new THREE.Vector3(
            this.player.position.x,
            this.player.position.y + 1.7,
            this.player.position.z + 6.2
        );
        this.camera.position.lerp(cameraTarget, 0.08);
        this.camera.lookAt(this.player.position.x, this.player.position.y + 0.9, this.player.position.z);
    }

    animateArena(elapsed) {
        if (this.zoneRing) {
            const t = (elapsed % this.zoneDuration) / this.zoneDuration;
            const radius = 23 - (t * 8.5);
            this.zoneRing.scale.setScalar(Math.max(0.58, radius / 23));
            this.zoneRing.material.opacity = 0.5 + Math.sin(elapsed * 2.0) * 0.18;
        }

        for (let i = 0; i < this.lootCrates.length; i += 1) {
            const crate = this.lootCrates[i];
            crate.rotation.y += 0.2 * (i % 2 === 0 ? 1 : -1) * 0.01;
        }
    }

    updateHud(elapsed) {
        if (elapsed - this.lastHudUpdate < 0.1) return;
        this.lastHudUpdate = elapsed;

        const phase = elapsed % this.zoneDuration;
        const remaining = Math.max(0, Math.ceil(this.zoneDuration - phase));

        const timerValue = document.getElementById('zone-timer');
        const pollutionFill = document.getElementById('pollution-fill');
        const healthFill = document.getElementById('health-fill');
        const shieldFill = document.getElementById('shield-fill');
        const healthValue = document.getElementById('health-value');
        const shieldValue = document.getElementById('shield-value');
        const killsCount = document.getElementById('kills-count');
        const aliveCount = document.getElementById('alive-count');
        const ammoCurrent = document.getElementById('ammo-current');
        const ammoReserve = document.getElementById('ammo-reserve');

        const selfState = this.getSelfState();
        const health = selfState?.health ?? Math.max(40, 100 - Math.floor((elapsed * 1.7) % 52));
        const shield = selfState?.shield ?? Math.max(25, 100 - Math.floor((elapsed * 2.2) % 66));

        if (timerValue) timerValue.textContent = `${remaining}s`;
        if (pollutionFill) pollutionFill.style.width = `${(phase / this.zoneDuration) * 100}%`;
        if (healthFill) healthFill.style.width = `${Math.max(0, Math.min(health, 100))}%`;
        if (shieldFill) shieldFill.style.width = `${Math.max(0, Math.min(shield, 100))}%`;
        if (healthValue) healthValue.textContent = String(Math.round(health));
        if (shieldValue) shieldValue.textContent = String(Math.round(shield));
        if (ammoCurrent) ammoCurrent.textContent = String(selfState?.ammo_current ?? 0);
        if (ammoReserve) ammoReserve.textContent = String(selfState?.ammo_reserve ?? 0);
        if (killsCount) {
            const totalBots = this.matchState?.bot_count ?? 0;
            const aliveBots = this.matchState?.alive_bots ?? totalBots;
            killsCount.textContent = String(Math.max(0, totalBots - aliveBots));
        }
        if (aliveCount) {
            const bots = this.matchState?.alive_bots ?? 0;
            const humans = this.matchState?.alive_humans ?? 1;
            aliveCount.textContent = String(bots + humans);
        }

        this.renderKillFeed();
        this.drawMinimap();
    }

    renderKillFeed() {
        const feedRoot = document.getElementById('kill-feed');
        if (!feedRoot) return;

        const feed = (this.matchState?.kill_feed || []).slice(0, 4);
        const signature = feed.map((entry) => `${entry.killer_name}:${entry.victim_name}:${entry.created_at}`).join('|');

        if (signature === this.lastFeedSignature) return;
        this.lastFeedSignature = signature;

        feedRoot.innerHTML = feed
            .map((entry) => {
                const hs = entry.headshot ? ' [HS]' : '';
                return `<div class="kill-entry">${entry.killer_name} eliminated ${entry.victim_name}${hs}</div>`;
            })
            .join('');
    }

    drawMinimap() {
        const canvas = document.getElementById('minimap');
        if (!(canvas instanceof HTMLCanvasElement) || !this.player) return;
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

        ctx.strokeStyle = 'rgba(163, 230, 53, 0.8)';
        ctx.beginPath();
        ctx.arc(center, center, size * 0.32, 0, Math.PI * 2);
        ctx.stroke();

        ctx.fillStyle = '#22d3ee';
        const px = center + (this.player.position.x / 90) * (size * 0.42);
        const py = center + (this.player.position.z / 90) * (size * 0.42);
        ctx.beginPath();
        ctx.arc(px, py, 4, 0, Math.PI * 2);
        ctx.fill();

        if (this.matchState?.players) {
            ctx.fillStyle = '#fb923c';
            for (const p of this.matchState.players) {
                if (!p.is_bot || !p.is_alive) continue;
                const bx = center + ((p.position?.x ?? 0) / 90) * (size * 0.42);
                const by = center + ((p.position?.z ?? 0) / 90) * (size * 0.42);
                ctx.beginPath();
                ctx.arc(bx, by, 3, 0, Math.PI * 2);
                ctx.fill();
            }
        }
    }

    async initializeWeapons() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/weapons`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                },
            });
            if (!response.ok) return;
            const data = await response.json();
            this.currentWeaponId = data?.weapons?.[0]?.id ?? null;
        } catch (error) {
            console.warn('[SurvivalArena] weapon init failed', error);
        }
    }

    async fetchMatchState() {
        if (!this.matchId) return;

        try {
            const response = await fetch(`${this.apiBaseUrl}/matches/${this.matchId}/state`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                },
            });
            if (!response.ok) return;
            const data = await response.json();
            this.matchState = data;
            this.syncServerState(data);
        } catch (error) {
            console.warn('[SurvivalArena] state poll failed', error);
        }
    }

    syncServerState(data) {
        if (!data) return;

        const botStates = (data.players || []).filter((p) => p.is_bot);
        this.botAI?.sync(botStates);

        const selfState = this.getSelfState();
        if (selfState?.position) {
            this.player.position.set(
                selfState.position.x ?? this.player.position.x,
                Math.max(1, selfState.position.y ?? this.player.position.y),
                selfState.position.z ?? this.player.position.z
            );
        }

        if ((data.alive_bots ?? 1) <= 0) {
            this.showVictory();
        }

        if ((selfState?.health ?? 1) <= 0) {
            this.showDeath();
        }

        if (data.status === 'finished' && !this.hasShownEndScreen) {
            const placement = data.player_summary?.placement ?? 99;
            if (placement === 1) {
                this.showVictory();
            } else {
                this.showDeath();
            }
        }
    }

    getSelfState() {
        return (this.matchState?.players || []).find((p) => !p.is_bot && p.player_id === this.userId) || null;
    }

    startNetworkLoops() {
        this.pollHandle = setInterval(() => {
            this.fetchMatchState();
        }, 350);
    }

    async sendPositionUpdate() {
        if (!this.matchId) return;

        const now = performance.now();
        if (now < this.nextPositionSyncAt) return;
        this.nextPositionSyncAt = now + 120;

        const velocity = this.player.userData.velocity || { x: 0, y: 0, z: 0 };

        const payload = {
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
                x: Number((velocity.x || 0).toFixed(3)),
                y: Number((velocity.y || 0).toFixed(3)),
                z: Number((velocity.z || 0).toFixed(3)),
            },
            is_sprinting: this.keys.has('ShiftLeft'),
            is_crouching: this.keys.has('ControlLeft'),
        };

        try {
            await fetch(`${this.apiBaseUrl}/matches/${this.matchId}/position`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(payload),
            });
        } catch (error) {
            console.warn('[SurvivalArena] position sync failed', error);
        }
    }

    async shoot() {
        if (!this.matchId || !this.currentWeaponId) return;

        const now = performance.now();
        if (now < this.nextShotAt) return;
        this.nextShotAt = now + 180;

        const dir = new THREE.Vector3();
        this.camera.getWorldDirection(dir);

        const payload = {
            direction: {
                x: Number(dir.x.toFixed(4)),
                y: Number(dir.y.toFixed(4)),
                z: Number(dir.z.toFixed(4)),
            },
            weapon_id: this.currentWeaponId,
        };

        try {
            const response = await fetch(`${this.apiBaseUrl}/matches/${this.matchId}/shoot`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                const result = await response.json();
                if (result.hit && result.kill) {
                    this.pushNotification(`Eliminated ${result.victim_name || 'target'}`);
                }
            }
        } catch (error) {
            console.warn('[SurvivalArena] shoot failed', error);
        }
    }

    async triggerReload() {
        const indicator = document.getElementById('reload-indicator');
        if (indicator) {
            indicator.classList.add('active');
            setTimeout(() => indicator.classList.remove('active'), 900);
        }

        if (!this.matchId) return;

        try {
            await fetch(`${this.apiBaseUrl}/matches/${this.matchId}/reload`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
            });
        } catch (error) {
            console.warn('[SurvivalArena] reload failed', error);
        }
    }

    showDeath() {
        const death = document.getElementById('death-screen');
        if (death) {
            death.classList.remove('hidden');
        }
        this.hasShownEndScreen = true;
        this.applyEndStats();
    }

    showVictory() {
        const victory = document.getElementById('victory-screen');
        if (victory) {
            victory.classList.remove('hidden');
        }
        this.hasShownEndScreen = true;
        this.applyEndStats();
    }

    applyEndStats() {
        const summary = this.matchState?.player_summary;
        if (!summary) return;

        const placement = summary.placement ? `#${summary.placement}` : '#-';
        const kills = summary.kills ?? 0;
        const survivalSeconds = summary.survival_time ?? 0;
        const minutes = Math.floor(survivalSeconds / 60);
        const seconds = survivalSeconds % 60;
        const survival = `${minutes}:${String(seconds).padStart(2, '0')}`;

        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = String(value);
        };

        setText('placement', placement);
        setText('final-kills', kills);
        setText('survival-time', survival);
        setText('win-placement', placement);
        setText('win-kills', kills);
        setText('win-xp', `Score ${summary.score ?? 0}`);
    }

    pushNotification(message) {
        const root = document.getElementById('notifications');
        if (!root) return;

        const entry = document.createElement('div');
        entry.className = 'notification-item';
        entry.textContent = message;
        root.appendChild(entry);

        setTimeout(() => {
            entry.remove();
        }, 1800);
    }

    updateLoading(text, progress) {
        const textEl = document.getElementById('loading-text');
        const fillEl = document.getElementById('loading-fill');
        if (textEl) textEl.textContent = text;
        if (fillEl) fillEl.style.width = `${progress}%`;
    }

    showRendererError() {
        const container = document.getElementById('game-container');
        if (container) {
            container.innerHTML = '<div class="scene-surface">WebGL is unavailable in this browser/device.</div>';
        }
        this.updateLoading('WebGL unavailable', 100);
    }

    finishLoading() {
        this.updateLoading('Match initialized', 100);
        setTimeout(() => {
            const screen = document.getElementById('loading-screen');
            if (screen) screen.classList.add('hidden');
        }, 350);
    }

    animate() {
        const loop = () => {
            const delta = this.clock.getDelta();
            const elapsed = this.clock.elapsedTime;

            this.handleMovement(delta);
            this.animateArena(elapsed);
            this.updateHud(elapsed);
            this.sendPositionUpdate();

            if (this.renderer && this.scene && this.camera) {
                this.renderer.render(this.scene, this.camera);
            }
            requestAnimationFrame(loop);
        };

        requestAnimationFrame(loop);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new SurvivalArenaBootstrap().init();
    });
} else {
    new SurvivalArenaBootstrap().init();
}
