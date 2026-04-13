<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LootSpawn extends Model
{
    protected $table = 'sa_loot_spawns';
    
    protected $fillable = [
        'match_id',
        'item_type',
        'item_id',
        'position',
        'is_collected',
        'collected_by',
        'spawned_at',
        'collected_at'
    ];

    protected $casts = [
        'position' => 'array',
        'is_collected' => 'boolean',
        'spawned_at' => 'datetime',
        'collected_at' => 'datetime'
    ];

    // ========== Relationships ==========
    
    public function match(): BelongsTo
    {
        return $this->belongsTo(ArenaMatch::class, 'match_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function weapon(): BelongsTo
    {
        return $this->belongsTo(Weapon::class, 'item_id');
    }

    // ========== Scopes ==========
    
    public function scopeUncollected($query)
    {
        return $query->where('is_collected', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    // ========== Methods ==========
    
    public function collect(User $user): void
    {
        $this->update([
            'is_collected' => true,
            'collected_by' => $user->id,
            'collected_at' => now()
        ]);

        // Broadcast pickup event
        broadcast(new \App\Events\SurvivalArena\Player\PlayerPickedUpItem(
            $this->match,
            $user->id,
            $this->id,
            $this->item_type
        ))->toOthers();
    }

    public function getItem()
    {
        return match($this->item_type) {
            'weapon' => Weapon::find($this->item_id),
            default => null
        };
    }

    public function toGameData(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->item_type,
            'itemId' => $this->item_id,
            'position' => $this->position,
            'isCollected' => $this->is_collected
        ];
    }
}

