import * as THREE from 'three';

export class CollisionService {
    constructor(boxes = []) {
        this.boxes = boxes;
        this.playerRadius = 0.5;
        this.blockingMeshes = [];
        this.raycaster = new THREE.Raycaster();
    }

    setBoxes(boxes) {
        this.boxes = boxes || [];
    }

    setBlockingMeshes(meshes) {
        this.blockingMeshes = meshes || [];
    }

    segmentBlocked(currentPosition, desiredPosition) {
        if (!this.blockingMeshes || this.blockingMeshes.length === 0) {
            return false;
        }

        const direction = desiredPosition.clone().sub(currentPosition);
        const length = direction.length();
        if (length <= 0.0001) {
            return false;
        }

        const origin = currentPosition.clone();
        origin.y += 0.9;
        const dir = direction.normalize();

        this.raycaster.set(origin, dir);
        this.raycaster.near = 0;
        this.raycaster.far = length + this.playerRadius;

        const intersections = this.raycaster.intersectObjects(this.blockingMeshes, true);
        return intersections.length > 0;
    }

    resolvePlayerMovement(currentPosition, desiredPosition, radius = this.playerRadius) {
        if (this.segmentBlocked(currentPosition, desiredPosition)) {
            return currentPosition.clone();
        }

        const next = currentPosition.clone();
        const targetX = desiredPosition.x;
        const targetZ = desiredPosition.z;

        next.x = targetX;
        for (const box of this.boxes) {
            if (!this.intersectsCylinder(box, next, radius)) {
                continue;
            }

            const currentX = currentPosition.x;
            if (targetX > currentX) {
                next.x = box.min.x - radius;
            } else if (targetX < currentX) {
                next.x = box.max.x + radius;
            } else {
                const centerX = (box.min.x + box.max.x) / 2;
                next.x = next.x >= centerX ? box.max.x + radius : box.min.x - radius;
            }
        }

        next.z = targetZ;
        for (const box of this.boxes) {
            if (!this.intersectsCylinder(box, next, radius)) {
                continue;
            }

            const currentZ = currentPosition.z;
            if (targetZ > currentZ) {
                next.z = box.min.z - radius;
            } else if (targetZ < currentZ) {
                next.z = box.max.z + radius;
            } else {
                const centerZ = (box.min.z + box.max.z) / 2;
                next.z = next.z >= centerZ ? box.max.z + radius : box.min.z - radius;
            }
        }

        return next;
    }

    intersectsCylinder(box, position, radius) {
        const boxCenter = box.getCenter(new THREE.Vector3());
        const boxSize = box.getSize(new THREE.Vector3());
        const halfHeight = boxSize.y / 2;
        if (Math.abs(position.y - boxCenter.y) > halfHeight + 1.0) {
            return false;
        }

        const clampedX = Math.max(box.min.x, Math.min(position.x, box.max.x));
        const clampedZ = Math.max(box.min.z, Math.min(position.z, box.max.z));
        const dx = position.x - clampedX;
        const dz = position.z - clampedZ;
        return (dx * dx + dz * dz) <= (radius * radius);
    }

    raycastDistance(origin, direction, maxDistance = 100) {
        const ray = new THREE.Ray(origin.clone(), direction.clone().normalize());
        let closest = maxDistance;

        for (const box of this.boxes) {
            const hitPoint = new THREE.Vector3();
            const hit = ray.intersectBox(box, hitPoint);
            if (!hit) {
                continue;
            }

            const distance = origin.distanceTo(hitPoint);
            if (distance < closest) {
                closest = distance;
            }
        }

        return closest;
    }

    lineBlocked(origin, direction, maxDistance = 100) {
        return this.raycastDistance(origin, direction, maxDistance) < maxDistance;
    }

    raycastBlockingDistance(origin, direction, maxDistance = 100) {
        let closest = maxDistance;

        if (!this.blockingMeshes || this.blockingMeshes.length === 0) {
            return closest;
        }

        this.raycaster.set(origin, direction.clone().normalize());
        this.raycaster.near = 0;
        this.raycaster.far = maxDistance;

        const intersections = this.raycaster.intersectObjects(this.blockingMeshes, true);
        if (intersections.length > 0) {
            closest = intersections[0].distance;
        }

        return closest;
    }
}
