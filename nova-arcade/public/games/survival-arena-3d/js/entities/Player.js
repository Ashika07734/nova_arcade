import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

export class Player {
    constructor() {
        this.group = new THREE.Group();
        this.velocity = new THREE.Vector3();
        this.isJumping = false;
        this.hasModel = false;
        this.loader = new GLTFLoader();
        this.visualRoot = new THREE.Group();
        this.group.add(this.visualRoot);
        this.fallbackEnabled = Boolean(window.gameData?.debugPlayerFallback);

        const body = new THREE.Mesh(
            new THREE.CapsuleGeometry(0.42, 1.02, 4, 12),
            new THREE.MeshStandardMaterial({ color: 0x38bdf8, roughness: 0.55, metalness: 0.18 })
        );
        body.castShadow = true;

        const visor = new THREE.Mesh(
            new THREE.SphereGeometry(0.18, 16, 16),
            new THREE.MeshStandardMaterial({ color: 0xe2e8f0, roughness: 0.35, metalness: 0.45 })
        );
        visor.position.set(0.05, 1.14, 0.08);
        visor.castShadow = true;

        this.fallbackBody = body;
        this.fallbackVisor = visor;
        this.fallbackBody.visible = this.fallbackEnabled;
        this.fallbackVisor.visible = this.fallbackEnabled;
        this.visualRoot.add(body, visor);
    }

    async loadModel(candidates = []) {
        const baseUrls = [...new Set(candidates.filter(Boolean))];
        const urls = [...baseUrls];

        for (const baseUrl of baseUrls) {
            if (typeof baseUrl !== 'string' || baseUrl.includes('?')) {
                continue;
            }
            urls.push(`${baseUrl}?v=sa-player-v2`);
        }

        for (const url of urls) {
            try {
                const gltf = await new Promise((resolve, reject) => {
                    this.loader.load(encodeURI(url), resolve, undefined, reject);
                });

                const model = gltf.scene;
                model.traverse((child) => {
                    if (child instanceof THREE.Mesh) {
                        child.castShadow = true;
                        child.receiveShadow = true;
                        child.frustumCulled = false;
                    }
                });

                const bounds = new THREE.Box3().setFromObject(model);
                const size = new THREE.Vector3();
                const center = new THREE.Vector3();
                bounds.getSize(size);
                bounds.getCenter(center);

                const sizeIsValid = Number.isFinite(size.x) && Number.isFinite(size.y) && Number.isFinite(size.z)
                    && size.x > 0.0001 && size.y > 0.0001 && size.z > 0.0001;
                const tallest = sizeIsValid ? Math.max(size.y, 1) : 1.7;
                const rawScale = 1.7 / tallest;
                const scale = THREE.MathUtils.clamp(rawScale, 0.25, 4.0);
                model.scale.setScalar(scale);

                const groundOffset = Number.isFinite(bounds.min.y) ? -bounds.min.y * scale : 0;
                const centerX = Number.isFinite(center.x) ? center.x : 0;
                const centerZ = Number.isFinite(center.z) ? center.z : 0;
                model.position.set(-centerX * scale, groundOffset, -centerZ * scale);

                this.visualRoot.clear();
                this.visualRoot.add(model);
                this.hasModel = true;
                return true;
            } catch (error) {
                console.warn(`[SurvivalArena] player model load failed: ${url}`, error);
            }
        }

        console.error('[SurvivalArena] player model failed for all candidates', urls);
        this.hasModel = false;
        if (!this.fallbackEnabled) {
            this.visualRoot.clear();
        }

        return false;
    }

    setPosition(position) {
        this.group.position.set(position.x, position.y, position.z);
    }

    get position() {
        return this.group.position;
    }

    get rotation() {
        return this.group.rotation;
    }

    update(delta, input, collisionService, heading = 0) {
        const move = new THREE.Vector3();
        if (input.forward) move.z += 1;
        if (input.backward) move.z -= 1;
        if (input.left) move.x += 1;
        if (input.right) move.x -= 1;

        const speed = input.sprint ? 8.4 : 5.2;
        if (move.lengthSq() > 0) {
            move.normalize().multiplyScalar(speed * delta);
            move.applyAxisAngle(new THREE.Vector3(0, 1, 0), heading);
            this.velocity.x = delta > 0 ? move.x / delta : 0;
            this.velocity.z = delta > 0 ? move.z / delta : 0;
        } else {
            this.velocity.x = 0;
            this.velocity.z = 0;
        }

        const desired = this.position.clone().add(move);
        const resolved = collisionService.resolvePlayerMovement(this.position, desired, 0.48);
        this.position.copy(resolved);

        this.velocity.y += (-9.81 * delta);
        this.position.y += this.velocity.y * delta;
        if (this.position.y <= 0.05) {
            this.position.y = 0.05;
            this.velocity.y = 0;
            this.isJumping = false;
        }

        if (input.jump && !this.isJumping) {
            this.velocity.y = 5.25;
            this.isJumping = true;
        }

        this.group.rotation.y = heading;
    }
}
