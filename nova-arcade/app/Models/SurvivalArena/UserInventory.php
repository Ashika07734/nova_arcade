<?php

namespace App\Models\SurvivalArena;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserInventory extends Model
{
    protected $table = 'sa_user_inventory';

    protected $fillable = [
        'user_id',
        'item_type',
        'item_id',
        'equipped',
        'unlocked_at',
    ];

    protected $casts = [
        'equipped' => 'boolean',
        'unlocked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    public function scopeEquipped($query)
    {
        return $query->where('equipped', true);
    }

    public function equip(): void
    {
        static::where('user_id', $this->user_id)
            ->where('item_type', $this->item_type)
            ->update(['equipped' => false]);

        $this->update(['equipped' => true]);
    }

    public function unequip(): void
    {
        $this->update(['equipped' => false]);
    }
}
