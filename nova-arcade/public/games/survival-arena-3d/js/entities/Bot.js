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
        tag.position.set(0, 1.9, 0);

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
        this.isAlive = true;

        this.loadModel([
            '/assets/models/s.w.a.t._operator-_4k_followers_special_remaster.glb',
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
                        // Bots are numerous, keep their model light for better frame pacing.
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

                model.position.y += 0.05;
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

    updateFromState(state) {
        const wasAlive = this.isAlive;
        this.health = state.health;
        this.isAlive = !!state.is_alive;

        this.group.position.set(
            state.position?.x ?? 0,
            Math.max(1, state.position?.y ?? 1),
            state.position?.z ?? 0
        );

        if (state.rotation?.y !== undefined) {
            this.group.rotation.y = state.rotation.y;
        }

        if (wasAlive && !this.isAlive) {
            this.deathStarted = true;
            this.deathProgress = 0;
        }

        this.group.visible = true;
    }

    update(delta) {
        if (!this.deathStarted) {
            return;
        }

        this.deathProgress += delta;
        this.group.rotation.z += delta * 1.5;
        this.group.position.y = Math.max(0.15, this.group.position.y - (delta * 0.9));
        this.group.scale.setScalar(Math.max(0, 1 - (this.deathProgress * 0.9)));

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
