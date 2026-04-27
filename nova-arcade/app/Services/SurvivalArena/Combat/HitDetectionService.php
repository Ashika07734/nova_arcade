<?php

namespace App\Services\SurvivalArena\Combat;

use App\Models\SurvivalArena\PlayerState;
use Illuminate\Support\Facades\Cache;

class HitDetectionService
{
    private float $playerHeight = 1.8;
    private float $headHeight = 0.3;
    private float $playerRadius = 0.5;
    
    /**
     * Perform raycast to detect hits
     */
    public function raycast(
        PlayerState $shooter,
        array $direction,
        float $maxRange
    ): ?array {
        // Get all potential targets
        $targets = $this->getPotentialTargets($shooter);
        
        $closestHit = null;
        $minDistance = PHP_FLOAT_MAX;
        
        foreach ($targets as $target) {
            $hit = $this->checkRayIntersection(
                $shooter->position,
                $direction,
                $target,
                $maxRange,
                $shooter->match->map_data['collision_boxes'] ?? $shooter->match->map_data['obstacles'] ?? []
            );
            
            if ($hit && $hit['distance'] < $minDistance) {
                $closestHit = $hit;
                $minDistance = $hit['distance'];
            }
        }
        
        return $closestHit;
    }
    
    /**
     * Get potential targets (players in range)
     */
    private function getPotentialTargets(PlayerState $shooter): array
    {
        $shooterKey = $shooter->user_id ?: ('bot:' . ($shooter->bot_name ?: $shooter->id));
        $cacheKey = "match:{$shooter->match_id}:targets:{$shooterKey}";
        
        return Cache::remember($cacheKey, now()->addMilliseconds(100), function () use ($shooter) {
            $states = PlayerState::where('match_id', $shooter->match_id)
                ->where('id', '!=', $shooter->id)
                ->get();

            return $states
                ->filter(function (PlayerState $state) use ($shooter) {
                    $isAlive = $this->isStateAlive($state);
                    if (!$isAlive) {
                        return false;
                    }

                    if (($shooter->is_bot ?? false) === true) {
                        return ($state->is_bot ?? false) === false;
                    }

                    return true;
                })
                ->map(fn (PlayerState $state) => $state->toArray())
                ->values()
                ->toArray();
        });
    }

    private function isStateAlive(PlayerState $state): bool
    {
        if (($state->is_bot ?? false) === true) {
            return $state->match->players()
                ->where('is_bot', true)
                ->where('bot_name', $state->bot_name)
                ->where('is_alive', true)
                ->exists();
        }

        return $state->match->players()
            ->where('user_id', $state->user_id)
            ->where('is_alive', true)
            ->exists();
    }
    
    /**
     * Check if ray intersects with player
     */
    private function checkRayIntersection(
        array $origin,
        array $direction,
        array $target,
        float $maxRange,
        array $obstacles = []
    ): ?array {
        $targetPos = $target['position'];
        
        // Calculate vector from ray origin to target
        $toTarget = [
            'x' => $targetPos['x'] - $origin['x'],
            'y' => $targetPos['y'] - $origin['y'],
            'z' => $targetPos['z'] - $origin['z']
        ];
        
        // Normalize direction
        $dirLength = sqrt(
            $direction['x'] ** 2 +
            $direction['y'] ** 2 +
            $direction['z'] ** 2
        );
        
        if ($dirLength == 0) {
            return null;
        }
        
        $normalizedDir = [
            'x' => $direction['x'] / $dirLength,
            'y' => $direction['y'] / $dirLength,
            'z' => $direction['z'] / $dirLength
        ];
        
        // Calculate dot product (projection)
        $dot = 
            $toTarget['x'] * $normalizedDir['x'] +
            $toTarget['y'] * $normalizedDir['y'] +
            $toTarget['z'] * $normalizedDir['z'];
        
        // Check if target is behind shooter
        if ($dot < 0) {
            return null;
        }
        
        // Check if beyond max range
        if ($dot > $maxRange) {
            return null;
        }

        if (!$this->hasLineOfSight($origin, $targetPos, $obstacles)) {
            return null;
        }
        
        // Calculate closest point on ray to target
        $closestPoint = [
            'x' => $origin['x'] + $normalizedDir['x'] * $dot,
            'y' => $origin['y'] + $normalizedDir['y'] * $dot,
            'z' => $origin['z'] + $normalizedDir['z'] * $dot
        ];
        
        // Calculate distance from target to closest point
        $distance = sqrt(
            pow($targetPos['x'] - $closestPoint['x'], 2) +
            pow($targetPos['y'] - $closestPoint['y'], 2) +
            pow($targetPos['z'] - $closestPoint['z'], 2)
        );
        
        // Check if within player hitbox radius
        if ($distance <= $this->playerRadius) {
            // Check if headshot
            $isHeadshot = $this->isHeadshot($closestPoint, $targetPos);
            
            return [
                'player' => PlayerState::where('match_id', $target['match_id'])
                    ->where('id', $target['id'])
                    ->first(),
                'distance' => $dot,
                'headshot' => $isHeadshot,
                'hit_position' => $closestPoint
            ];
        }
        
        return null;
    }
    
    /**
     * Check if hit is a headshot
     */
    private function isHeadshot(array $hitPosition, array $targetPosition): bool
    {
        $headMinY = $targetPosition['y'] + $this->playerHeight - $this->headHeight;
        $headMaxY = $targetPosition['y'] + $this->playerHeight;
        
        return $hitPosition['y'] >= $headMinY && $hitPosition['y'] <= $headMaxY;
    }
    
    /**
     * Check line of sight between two points
     */
    public function hasLineOfSight(array $from, array $to, array $obstacles = []): bool
    {
        foreach ($obstacles as $obstacle) {
            if ($this->lineIntersectsObstacle($from, $to, $obstacle)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if line intersects with obstacle
     */
    private function lineIntersectsObstacle(array $from, array $to, array $obstacle): bool
    {
        // Simplified AABB intersection check
        // This would be more complex in a real implementation
        
        $minX = $obstacle['x'] - $obstacle['width'] / 2;
        $maxX = $obstacle['x'] + $obstacle['width'] / 2;
        $minZ = $obstacle['z'] - $obstacle['depth'] / 2;
        $maxZ = $obstacle['z'] + $obstacle['depth'] / 2;
        
        // Check if either point is inside obstacle
        if ($this->pointInBox($from, $minX, $maxX, $minZ, $maxZ) ||
            $this->pointInBox($to, $minX, $maxX, $minZ, $maxZ)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if point is inside box
     */
    private function pointInBox(
        array $point,
        float $minX,
        float $maxX,
        float $minZ,
        float $maxZ
    ): bool {
        return $point['x'] >= $minX && $point['x'] <= $maxX &&
               $point['z'] >= $minZ && $point['z'] <= $maxZ;
    }
}

