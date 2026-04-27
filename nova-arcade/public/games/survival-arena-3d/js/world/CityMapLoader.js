import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

export class CityMapLoader {
    constructor(scene) {
        this.scene = scene;
        this.loader = new GLTFLoader();
        this.collisionBoxes = [];
        this.mapRoot = null;
    }

    async load(url, mapData = {}) {
        const gltf = await this.loadGLB(url);
        const root = gltf.scene;
        this.mapRoot = root;

        this.applyLighting();
        this.prepareScene(root);
        this.fitMapToWorld(root, mapData.size ?? 320);
        this.collisionBoxes = this.prepareCollisions(root, mapData);
        const blockingMeshes = this.collectBlockingMeshes(root);

        this.scene.add(root);

        let meshCount = 0;
        root.traverse((child) => {
            if (child instanceof THREE.Mesh) {
                meshCount += 1;
            }
        });

        return {
            root,
            collisionBoxes: this.collisionBoxes,
            blockingMeshes,
            mapData,
            meshCount,
        };
    }

    collectBlockingMeshes(root) {
        const meshes = [];

        root.traverse((child) => {
            if (!(child instanceof THREE.Mesh)) {
                return;
            }

            const bounds = new THREE.Box3().setFromObject(child);
            const size = new THREE.Vector3();
            bounds.getSize(size);

            if (size.y < 2.2) {
                return;
            }

            const name = (child.name || '').toLowerCase();
            if (name.includes('road') || name.includes('street') || name.includes('ground') || name.includes('floor')) {
                return;
            }

            meshes.push(child);
        });

        return meshes;
    }

    findGroundSpawn(candidates = [], options = {}) {
        const originHeight = options.originHeight ?? 220;
        const offsetY = options.offsetY ?? 0.95;
        const preferred = candidates.length > 0 ? candidates : [{ x: 10, z: 20 }];
        const hits = [];

        for (const candidate of this.buildWorldSampleCandidates(preferred)) {
            const hit = this.castDown(candidate, originHeight);
            if (hit && this.isValidGroundHit(hit)) {
                hits.push(hit);
            }
        }

        if (hits.length > 0) {
            hits.sort((a, b) => a.y - b.y);
            const ground = hits[0];

            return {
                x: ground.x,
                y: ground.y + offsetY,
                z: ground.z,
            };
        }

        const fallback = preferred[0];
        return {
            x: fallback.x,
            y: offsetY,
            z: fallback.z,
        };
    }

    buildWorldSampleCandidates(preferred = []) {
        if (!this.mapRoot) {
            return preferred;
        }

        this.mapRoot.updateMatrixWorld(true);
        const bounds = new THREE.Box3().setFromObject(this.mapRoot);
        const size = new THREE.Vector3();
        bounds.getSize(size);

        const samples = [];
        const gridSteps = 7;
        const marginX = Math.max(8, size.x * 0.08);
        const marginZ = Math.max(8, size.z * 0.08);
        const minX = bounds.min.x + marginX;
        const maxX = bounds.max.x - marginX;
        const minZ = bounds.min.z + marginZ;
        const maxZ = bounds.max.z - marginZ;

        for (let xStep = 0; xStep <= gridSteps; xStep += 1) {
            const x = THREE.MathUtils.lerp(minX, maxX, xStep / gridSteps);

            for (let zStep = 0; zStep <= gridSteps; zStep += 1) {
                const z = THREE.MathUtils.lerp(minZ, maxZ, zStep / gridSteps);
                samples.push({ x, z });
            }
        }

        return [...preferred, ...samples];
    }

    isValidGroundHit(hit) {
        const objectName = String(hit.objectName || '').toLowerCase();
        const invalidNames = ['roof', 'rooftop', 'tower', 'top', 'building', 'skyscraper', 'balcony', 'ledge'];
        const normalY = hit.face?.normal?.y ?? 1;

        if (normalY < 0.35) {
            return false;
        }

        return !invalidNames.some((token) => objectName.includes(token));
    }

    castDown(point, originHeight = 220) {
        if (!this.mapRoot) {
            return null;
        }

        this.mapRoot.updateMatrixWorld(true);

        const origin = new THREE.Vector3(point.x, originHeight, point.z);
        const raycaster = new THREE.Raycaster(origin, new THREE.Vector3(0, -1, 0), 0, originHeight + 20);
        const intersections = raycaster.intersectObject(this.mapRoot, true);

        if (intersections.length === 0) {
            return null;
        }

        const validHits = intersections.filter((hit) => {
            const hitY = hit.point?.y ?? (originHeight - hit.distance);
            return this.isValidGroundHit({
                y: hitY,
                objectName: hit.object?.name ?? null,
                face: hit.face,
            });
        });

        if (validHits.length === 0) {
            return null;
        }

        validHits.sort((a, b) => (a.point?.y ?? 9999) - (b.point?.y ?? 9999));
        const hit = validHits[0];
        const hitY = hit.point?.y ?? (originHeight - hit.distance);

        return {
            x: point.x,
            y: hitY,
            z: point.z,
            objectName: hit.object?.name ?? null,
            distance: hit.distance,
        };
    }

    async loadGLB(url) {
        return new Promise((resolve, reject) => {
            const timer = window.setTimeout(() => {
                reject(new Error(`City map load timeout: ${url}`));
            }, 8000);

            this.loader.load(
                encodeURI(url),
                (result) => {
                    window.clearTimeout(timer);
                    resolve(result);
                },
                undefined,
                (error) => {
                    window.clearTimeout(timer);
                    reject(error);
                }
            );
        });
    }

    applyLighting() {
        const ambient = new THREE.AmbientLight(0xdbeafe, 0.55);
        const hemi = new THREE.HemisphereLight(0x93c5fd, 0x0f172a, 0.9);
        const sun = new THREE.DirectionalLight(0xffffff, 1.1);

        sun.position.set(70, 130, 60);
        sun.castShadow = true;
        sun.shadow.mapSize.set(2048, 2048);
        sun.shadow.camera.near = 10;
        sun.shadow.camera.far = 320;
        sun.shadow.camera.left = -150;
        sun.shadow.camera.right = 150;
        sun.shadow.camera.top = 150;
        sun.shadow.camera.bottom = -150;

        this.scene.add(ambient, hemi, sun);
    }

    prepareScene(root) {
        root.traverse((child) => {
            if (!(child instanceof THREE.Mesh)) {
                return;
            }

            child.castShadow = true;
            child.receiveShadow = true;
            child.frustumCulled = true;
        });
    }

    fitMapToWorld(root, targetSize) {
        const box = new THREE.Box3().setFromObject(root);
        const size = new THREE.Vector3();
        box.getSize(size);

        const longestSide = Math.max(size.x, size.z, 1);
        this.worldScale = (targetSize / longestSide) * 0.9;
        root.scale.setScalar(this.worldScale);

        const reboxed = new THREE.Box3().setFromObject(root);
        const center = new THREE.Vector3();
        reboxed.getCenter(center);
        
        // Move center to world origin on X/Z only, keep Y at bottom = 0
        root.position.x -= center.x;
        root.position.z -= center.z;
        root.position.y -= reboxed.min.y;
    }

    prepareCollisions(root, mapData) {
        const boxes = [];
        const scale = this.worldScale || 1;
        const offsetX = root.position.x || 0;
        const offsetY = root.position.y || 0;
        const offsetZ = root.position.z || 0;

        const knownBoxes = mapData.collision_boxes ?? mapData.obstacles ?? [];
        if (knownBoxes.length > 0) {
            for (const item of knownBoxes) {
                const box = new THREE.Box3(
                    new THREE.Vector3(
                        offsetX + item.x - (item.width / 2),
                        offsetY + (item.y ?? 0),
                        offsetZ + item.z - (item.depth / 2)
                    ),
                    new THREE.Vector3(
                        offsetX + item.x + (item.width / 2),
                        offsetY + (item.y ?? 0) + (item.height ?? 10),
                        offsetZ + item.z + (item.depth / 2)
                    )
                );
                box.name = item.name || item.type || 'collider';
                boxes.push(box);
            }
        }

        root.traverse((child) => {
            if (!(child instanceof THREE.Mesh)) {
                return;
            }

            const bounds = new THREE.Box3().setFromObject(child);
            const size = new THREE.Vector3();
            bounds.getSize(size);
            const area = size.x * size.z;

            if (size.y < 2.8 || area < 14) {
                return;
            }

            const name = (child.name || '').toLowerCase();
            const blockedTokens = ['road', 'street', 'ground', 'floor', 'terrain', 'sidewalk'];
            if (blockedTokens.some((token) => name.includes(token))) {
                return;
            }

            boxes.push(bounds.clone());
        });

        return boxes;
    }
}
