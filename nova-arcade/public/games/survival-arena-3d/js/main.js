// public/games/survival-arena-3d/js/main.js
import * as THREE from 'three';

class SurvivalArenaBootstrap {
    constructor() {
        this.tick = 0;
        this.zoneDuration = 60;
        this.alive = Number.parseInt(document.getElementById('alive-count')?.textContent || '1', 10);
        this.keys = new Set();

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.clock = new THREE.Clock();
        this.player = null;
        this.zoneRing = null;
        this.lootCrates = [];
        this.lastHudUpdate = 0;
    }

    init() {
        this.updateLoading('Booting renderer', 15);
        const created = this.createScene();
        if (!created) {
            this.showRendererError();
            return;
        }

        this.updateLoading('Building arena', 45);
        this.buildArena();

        this.updateLoading('Binding controls', 70);
        this.bindUI();
        this.bindResize();

        this.updateLoading('Finalizing HUD', 92);
        this.finishLoading();
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

    bindUI() {
        window.addEventListener('keydown', (event) => {
            this.keys.add(event.code);
            if (event.code === 'KeyR') {
                const indicator = document.getElementById('reload-indicator');
                if (!indicator) return;
                indicator.classList.add('active');
                setTimeout(() => indicator.classList.remove('active'), 900);
            }
        });

        window.addEventListener('keyup', (event) => {
            this.keys.delete(event.code);
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

        const health = Math.max(40, 100 - Math.floor((elapsed * 1.7) % 52));
        const shield = Math.max(25, 100 - Math.floor((elapsed * 2.2) % 66));

        if (timerValue) timerValue.textContent = `${remaining}s`;
        if (pollutionFill) pollutionFill.style.width = `${(phase / this.zoneDuration) * 100}%`;
        if (healthFill) healthFill.style.width = `${health}%`;
        if (shieldFill) shieldFill.style.width = `${shield}%`;
        if (healthValue) healthValue.textContent = String(health);
        if (shieldValue) shieldValue.textContent = String(shield);
        if (killsCount) killsCount.textContent = String(Math.floor(elapsed / 42));

        this.drawMinimap();
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

            if (this.renderer && this.scene && this.camera) {
                this.renderer.render(this.scene, this.camera);
            }
            requestAnimationFrame(loop);
        };

        requestAnimationFrame(loop);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new SurvivalArenaBootstrap().init());
} else {
    new SurvivalArenaBootstrap().init();
}
