import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

export class Weapon {
    constructor(scene) {
        this.scene = scene;
        this.loader = new GLTFLoader();
        this.group = new THREE.Group();
        this.muzzle = new THREE.Object3D();
        this.muzzle.position.set(0.42, 0.55, -0.85);

        const fallback = new THREE.Mesh(
            new THREE.BoxGeometry(0.8, 0.12, 0.15),
            new THREE.MeshStandardMaterial({ color: 0x334155, roughness: 0.65, metalness: 0.2 })
        );
        fallback.position.set(0.08, 0.48, -0.06);
        fallback.castShadow = true;
        this.group.add(fallback, this.muzzle);
    }

    async load(url) {
        try {
            const gltf = await new Promise((resolve, reject) => {
                this.loader.load(encodeURI(url), resolve, undefined, reject);
            });

            const model = gltf.scene;
            model.traverse((child) => {
                if (child instanceof THREE.Mesh) {
                    child.castShadow = true;
                    child.receiveShadow = true;
                    child.frustumCulled = true;
                }
            });

            this.group.add(model);
        } catch (error) {
            console.warn(`[SurvivalArena] weapon load failed: ${url}`, error);
        }

        return this;
    }

    attachTo(playerGroup) {
        playerGroup.add(this.group);
        this.group.position.set(0.12, 0.05, 0.14);
        this.group.rotation.y = Math.PI / 2;
    }

    getMuzzleWorldPosition() {
        const point = new THREE.Vector3();
        this.muzzle.getWorldPosition(point);
        return point;
    }

    flashMuzzle(scene) {
        const flash = new THREE.PointLight(0xfde68a, 1.6, 8, 2);
        flash.position.copy(this.getMuzzleWorldPosition());
        scene.add(flash);
        window.setTimeout(() => scene.remove(flash), 50);
    }
}
