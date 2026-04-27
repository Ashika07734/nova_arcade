import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

export class Bullet {
    static loader = new GLTFLoader();
    static modelPromise = null;

    constructor(scene, origin, direction, collisionService, options = {}) {
        this.scene = scene;
        this.origin = origin.clone();
        this.direction = direction.clone().normalize();
        this.collisionService = collisionService;
        this.speed = options.speed ?? 180;
        this.maxDistance = options.maxDistance ?? 120;
        this.travelled = 0;
        this.finished = false;
        this.usesFallback = true;

        this.mesh = new THREE.Mesh(
            new THREE.SphereGeometry(0.05, 8, 8),
            new THREE.MeshBasicMaterial({ color: 0xf8fafc })
        );
        this.mesh.castShadow = false;
        this.mesh.position.copy(this.origin);
        this.scene.add(this.mesh);

        this.trySwapToModel();
    }

    static loadModel() {
        if (Bullet.modelPromise) {
            return Bullet.modelPromise;
        }

        Bullet.modelPromise = new Promise((resolve, reject) => {
            Bullet.loader.load(
                '/assets/models/sniper_bullet.glb',
                (gltf) => resolve(gltf.scene),
                undefined,
                reject
            );
        });

        return Bullet.modelPromise;
    }

    async trySwapToModel() {
        try {
            const source = await Bullet.loadModel();
            if (this.finished) {
                return;
            }

            const model = source.clone(true);
            model.traverse((child) => {
                if (child instanceof THREE.Mesh) {
                    child.castShadow = false;
                    child.receiveShadow = false;
                }
            });

            const bounds = new THREE.Box3().setFromObject(model);
            const size = new THREE.Vector3();
            const center = new THREE.Vector3();
            bounds.getSize(size);
            bounds.getCenter(center);

            const longest = Math.max(size.x, size.y, size.z, 0.001);
            const scale = 0.12 / longest;
            model.scale.setScalar(scale);
            model.position.set(-center.x * scale, -center.y * scale, -center.z * scale);

            this.scene.remove(this.mesh);
            this.mesh.geometry.dispose();
            this.mesh.material.dispose();

            this.mesh = model;
            this.mesh.position.copy(this.origin);
            this.scene.add(this.mesh);
            this.usesFallback = false;
        } catch (error) {
            console.warn('[SurvivalArena] bullet model load failed, using fallback', error);
        }
    }

    update(delta) {
        if (this.finished) {
            return;
        }

        const step = this.speed * delta;
        const next = this.mesh.position.clone().add(this.direction.clone().multiplyScalar(step));
        const blockedDistance = this.collisionService.raycastDistance(this.mesh.position, this.direction, step);

        if (blockedDistance < step) {
            this.mesh.position.add(this.direction.clone().multiplyScalar(blockedDistance - 0.08));
            this.finished = true;
            this.createImpact();
            return;
        }

        this.mesh.position.copy(next);
        this.mesh.lookAt(next.clone().add(this.direction));
        this.travelled += step;

        if (this.travelled >= this.maxDistance) {
            this.finished = true;
            this.dispose();
        }
    }

    createImpact() {
        const impact = new THREE.PointLight(0xfca5a5, 1.8, 6, 2);
        impact.position.copy(this.mesh.position);
        this.scene.add(impact);
        window.setTimeout(() => {
            this.scene.remove(impact);
        }, 80);
        this.dispose();
    }

    dispose() {
        this.scene.remove(this.mesh);

        if (this.usesFallback) {
            this.mesh.geometry.dispose();
            this.mesh.material.dispose();
        }
    }
}
