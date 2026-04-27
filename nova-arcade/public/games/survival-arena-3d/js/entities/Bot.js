import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

export class Bot {
    static loader = new GLTFLoader();

    constructor(id, label = 'BOT') {
        this.id = id;
        this.group = new THREE.Group();
        this.deathProgress = 0;
        this.deathStarted = false;
        this.visualRoot = new THREE.Group();
        this.group.add(this.visualRoot);

        const body = new THREE.Mesh(
            new THREE.CapsuleGeometry(0.42, 1.0, 4, 10),
            new THREE.MeshStandardMaterial({ color: 0xf97316, roughness: 0.55, metalness: 0.15 })
        );
        body.castShadow = true;

        const head = new THREE.Mesh(
            new THREE.SphereGeometry(0.22, 16, 16),
            new THREE.MeshStandardMaterial({ color: 0xf59e0b, roughness: 0.35, metalness: 0.1 })
        );
        head.position.set(0, 1.1, 0);
        head.castShadow = true;

        const weapon = new THREE.Mesh(
            new THREE.BoxGeometry(0.7, 0.09, 0.14),
            new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.7 })
        );
        weapon.position.set(0.3, 0.62, 0.12);

        const tag = this.createTag(label);
        tag.position.set(0, 2.2, 0);

        this.fallbackBody = body;
        this.fallbackHead = head;
        this.fallbackWeapon = weapon;
        this.fallbackBody.material.emissive = new THREE.Color(0x7c2d12);
        this.fallbackBody.material.emissiveIntensity = 0.65;
        this.fallbackHead.material.emissive = new THREE.Color(0xb45309);
        this.fallbackHead.material.emissiveIntensity = 0.45;
        this.visualRoot.add(body, head, weapon);
        this.group.add(tag);
        this.health = 100;
        this.maxHealth = 100;
        this.isAlive = true;

        // --- Health bar (floating above the bot) ---
        this.healthBarGroup = new THREE.Group();
        this.healthBarGroup.position.set(0, 2.0, 0);

        // Background bar (dark)
        const bgGeo = new THREE.PlaneGeometry(1.2, 0.12);
        const bgMat = new THREE.MeshBasicMaterial({ color: 0x1e293b, transparent: true, opacity: 0.85, side: THREE.DoubleSide, depthTest: false });
        this.healthBarBg = new THREE.Mesh(bgGeo, bgMat);
        this.healthBarBg.renderOrder = 999;
        this.healthBarGroup.add(this.healthBarBg);

        // Fill bar (green → yellow → red based on health)
        const fillGeo = new THREE.PlaneGeometry(1.16, 0.08);
        const fillMat = new THREE.MeshBasicMaterial({ color: 0x22c55e, transparent: true, opacity: 0.95, side: THREE.DoubleSide, depthTest: false });
        this.healthBarFill = new THREE.Mesh(fillGeo, fillMat);
        this.healthBarFill.renderOrder = 1000;
        this.healthBarFill.position.z = 0.001;
        this.healthBarGroup.add(this.healthBarFill);

        this.group.add(this.healthBarGroup);

        // --- Damage flash timer ---
        this.damageFlashTime = 0;

        this.loadModel([
            new URL('../../../assets/models/s.w.a.t._operator.glb', import.meta.url).toString(),
            window.gameData?.assetBaseUrl
                ? `${window.gameData.assetBaseUrl.replace(/\/$/, '')}/assets/models/s.w.a.t._operator.glb`
                : null,
            window.gameData?.playerAsset,
            '/assets/models/s.w.a.t._operator.glb',
        ]);
    }

    async loadModel(candidates = []) {
        for (const url of [...new Set(candidates.filter(Boolean))]) {
            try {
                const gltf = await new Promise((resolve, reject) => {
                    Bot.loader.load(encodeURI(url), resolve, undefined, reject);
                });

                const model = gltf.scene;
                model.traverse((child) => {
                    if (child instanceof THREE.Mesh) {
                        child.castShadow = false;
                        child.receiveShadow = false;
                        child.frustumCulled = true;
                    }
                });

                const bounds = new THREE.Box3().setFromObject(model);
                const size = new THREE.Vector3();
                const center = new THREE.Vector3();
                bounds.getSize(size);
                bounds.getCenter(center);

                const tallest = Math.max(size.y, 1);
                const scale = 1.7 / tallest;
                model.scale.setScalar(scale);
                model.position.set(-center.x * scale, -bounds.min.y * scale, -center.z * scale);

                this.visualRoot.clear();
                this.visualRoot.add(model);
                return true;
            } catch (error) {
                console.warn(`[SurvivalArena] bot model load failed: ${url}`, error);
            }
        }

        return false;
    }

    createTag(text) {
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 64;
        const ctx = canvas.getContext('2d');
        if (ctx) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'rgba(2,6,23,0.85)';
            ctx.fillRect(6, 8, canvas.width - 12, canvas.height - 16);
            ctx.fillStyle = '#f8fafc';
            ctx.font = 'bold 28px Rajdhani';
            ctx.textAlign = 'center';
            ctx.fillText(text, canvas.width / 2, 42);
        }

        const texture = new THREE.CanvasTexture(canvas);
        const material = new THREE.SpriteMaterial({ map: texture, transparent: true });
        const sprite = new THREE.Sprite(material);
        sprite.scale.set(2.6, 0.7, 1);
        return sprite;
    }

    /** Update health bar visual based on current health */
    updateHealthBar() {
        if (!this.healthBarFill || !this.healthBarBg) return;

        const pct = Math.max(0, this.health) / this.maxHealth;

        // Scale the fill bar horizontally
        this.healthBarFill.scale.x = pct;
        // Offset to keep it left-aligned
        this.healthBarFill.position.x = -(1.16 * (1 - pct)) / 2;

        // Color: green → yellow → red
        if (pct > 0.6) {
            this.healthBarFill.material.color.setHex(0x22c55e); // green
        } else if (pct > 0.3) {
            this.healthBarFill.material.color.setHex(0xeab308); // yellow
        } else {
            this.healthBarFill.material.color.setHex(0xef4444); // red
        }

        // Hide health bar when dead
        this.healthBarGroup.visible = this.isAlive && this.health < this.maxHealth;
    }

    /** Called when bot takes damage — triggers visual flash */
    onDamaged(damage) {
        this.health = Math.max(0, this.health - damage);
        this.damageFlashTime = 0.15;
        this.updateHealthBar();

        // Flash the model red briefly
        this.visualRoot.traverse((child) => {
            if (child instanceof THREE.Mesh && child.material) {
                const mat = child.material;
                if (!mat._origEmissive) {
                    mat._origEmissive = mat.emissive ? mat.emissive.clone() : new THREE.Color(0, 0, 0);
                    mat._origEmissiveIntensity = mat.emissiveIntensity ?? 0;
                }
                mat.emissive = new THREE.Color(0xff0000);
                mat.emissiveIntensity = 0.8;
            }
        });
    }

    updateFromState(state) {
        const wasAlive = this.isAlive;
        this.health = state.health;
        this.isAlive = !!state.is_alive;

        this.group.position.set(
            state.position?.x ?? 0,
            Math.max(0.05, state.position?.y ?? 0.05),
            state.position?.z ?? 0
        );

        if (state.rotation?.y !== undefined) {
            this.group.rotation.y = state.rotation.y;
        }

        if (wasAlive && !this.isAlive) {
            this.deathStarted = true;
            this.deathProgress = 0;
        }

        this.updateHealthBar();
        this.group.visible = true;
    }

    update(delta, cameraRef) {
        // Make health bar always face the camera
        if (cameraRef && this.healthBarGroup) {
            this.healthBarGroup.quaternion.copy(cameraRef.quaternion);
        }

        // Damage flash fade-out
        if (this.damageFlashTime > 0) {
            this.damageFlashTime -= delta;
            if (this.damageFlashTime <= 0) {
                this.damageFlashTime = 0;
                // Restore original emissive
                this.visualRoot.traverse((child) => {
                    if (child instanceof THREE.Mesh && child.material && child.material._origEmissive) {
                        child.material.emissive.copy(child.material._origEmissive);
                        child.material.emissiveIntensity = child.material._origEmissiveIntensity;
                    }
                });
            }
        }

        if (!this.deathStarted) {
            return;
        }

        this.deathProgress += delta;
        this.group.rotation.z += delta * 1.5;
        this.group.position.y = Math.max(0.15, this.group.position.y - (delta * 0.9));
        this.group.scale.setScalar(Math.max(0, 1 - (this.deathProgress * 0.9)));

        // Hide health bar on death
        if (this.healthBarGroup) {
            this.healthBarGroup.visible = false;
        }

        if (this.deathProgress >= 1.1) {
            this.group.visible = false;
        }
    }

    dispose() {
        this.group.traverse((child) => {
            if (child instanceof THREE.Mesh) {
                child.geometry?.dispose();
                if (Array.isArray(child.material)) {
                    child.material.forEach((mat) => mat.dispose?.());
                } else {
                    child.material?.dispose?.();
                }
            }

            if (child instanceof THREE.Sprite) {
                child.material?.map?.dispose?.();
                child.material?.dispose?.();
            }
        });
    }
}
