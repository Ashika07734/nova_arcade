<?php

namespace App\Http\Controllers\SurvivalArena;

use App\Http\Controllers\Controller;
use App\Models\SurvivalArena\UserInventory;
use App\Models\SurvivalArena\Weapon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    /**
     * Display user inventory
     */
    public function index()
    {
        $user = Auth::user();

        // Get all inventory items grouped by type
        $inventory = [
            'character_skins' => $this->getInventoryByType($user, 'character_skin'),
            'weapon_skins' => $this->getInventoryByType($user, 'weapon_skin'),
            'emotes' => $this->getInventoryByType($user, 'emote'),
            'banners' => $this->getInventoryByType($user, 'banner'),
            'titles' => $this->getInventoryByType($user, 'title'),
        ];

        // Get equipped items
        $equipped = [
            'character_skin' => $this->getEquippedItem($user, 'character_skin'),
            'weapon_skin' => $this->getEquippedItem($user, 'weapon_skin'),
            'emote' => $this->getEquippedItem($user, 'emote'),
            'banner' => $this->getEquippedItem($user, 'banner'),
            'title' => $this->getEquippedItem($user, 'title'),
        ];

        return view('survival-arena.inventory', compact('inventory', 'equipped'));
    }

    /**
     * Get inventory items by type
     */
    private function getInventoryByType($user, string $type)
    {
        return $user->inventory()
            ->byType($type)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_id' => $item->item_id,
                    'item_type' => $item->item_type,
                    'equipped' => $item->equipped,
                    'unlocked_at' => $item->unlocked_at->diffForHumans(),
                    // Add item details here based on item_type
                    'name' => $this->getItemName($item),
                    'image' => $this->getItemImage($item),
                    'rarity' => $this->getItemRarity($item)
                ];
            });
    }

    /**
     * Get equipped item
     */
    private function getEquippedItem($user, string $type)
    {
        return $user->inventory()
            ->byType($type)
            ->equipped()
            ->first();
    }

    /**
     * Get item name based on type and ID
     */
    private function getItemName(UserInventory $item): string
    {
        // This would query the respective table based on item_type
        // For now, return a placeholder
        return match($item->item_type) {
            'character_skin' => "Character Skin #{$item->item_id}",
            'weapon_skin' => "Weapon Skin #{$item->item_id}",
            'emote' => "Emote #{$item->item_id}",
            'banner' => "Banner #{$item->item_id}",
            'title' => "Title #{$item->item_id}",
            default => "Item #{$item->item_id}"
        };
    }

    /**
     * Get item image
     */
    private function getItemImage(UserInventory $item): string
    {
        return asset("assets/images/inventory/{$item->item_type}/{$item->item_id}.png");
    }

    /**
     * Get item rarity
     */
    private function getItemRarity(UserInventory $item): string
    {
        // This would query the respective table
        return 'common';
    }

    /**
     * Equip an item
     */
    public function equip(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:sa_user_inventory,id'
        ]);

        $item = UserInventory::where('id', $validated['inventory_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $item->equip();

        return response()->json([
            'success' => true,
            'message' => 'Item equipped successfully'
        ]);
    }

    /**
     * Unequip an item
     */
    public function unequip(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:sa_user_inventory,id'
        ]);

        $item = UserInventory::where('id', $validated['inventory_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $item->unequip();

        return response()->json([
            'success' => true,
            'message' => 'Item unequipped successfully'
        ]);
    }

    /**
     * Get inventory data (API)
     */
    public function getData()
    {
        $user = Auth::user();

        $inventory = $user->inventory()
            ->get()
            ->groupBy('item_type')
            ->map(function ($items) {
                return $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'item_id' => $item->item_id,
                        'item_type' => $item->item_type,
                        'equipped' => $item->equipped,
                        'unlocked_at' => $item->unlocked_at->timestamp,
                        'name' => $this->getItemName($item),
                        'image' => $this->getItemImage($item),
                        'rarity' => $this->getItemRarity($item)
                    ];
                });
            });

        return response()->json(['inventory' => $inventory]);
    }

    /**
     * Unlock item (admin/achievement)
     */
    public function unlock(Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'required|in:character_skin,weapon_skin,emote,banner,title',
            'item_id' => 'required|integer'
        ]);

        $user = Auth::user();

        // Check if already unlocked
        $exists = $user->inventory()
            ->where('item_type', $validated['item_type'])
            ->where('item_id', $validated['item_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Item already unlocked'
            ], 400);
        }

        // Unlock item
        $item = $user->inventory()->create([
            'item_type' => $validated['item_type'],
            'item_id' => $validated['item_id'],
            'equipped' => false,
            'unlocked_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item unlocked successfully',
            'item' => [
                'id' => $item->id,
                'name' => $this->getItemName($item),
                'image' => $this->getItemImage($item)
            ]
        ]);
    }

    /**
     * Get available items to unlock (shop/rewards)
     */
    public function available(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type');

        // Get items user doesn't have yet
        $ownedItemIds = $user->inventory()
            ->when($type, fn($q) => $q->where('item_type', $type))
            ->pluck('item_id', 'item_type')
            ->groupBy(fn($item, $key) => $key);

        // This would query actual item tables
        // For now, return placeholder data
        $availableItems = [
            [
                'item_type' => 'character_skin',
                'item_id' => 1,
                'name' => 'Neon Warrior',
                'description' => 'A futuristic neon-themed character skin',
                'rarity' => 'epic',
                'unlock_requirement' => 'Reach Level 10',
                'cost' => null, // or XP/currency cost
                'image' => asset('assets/images/skins/neon_warrior.png')
            ],
            // More items...
        ];

        return response()->json(['items' => $availableItems]);
    }

    /**
     * Preview equipped loadout
     */
    public function loadout()
    {
        $user = Auth::user();

        $loadout = [
            'character_skin' => $this->getEquippedItem($user, 'character_skin'),
            'weapon_skin' => $this->getEquippedItem($user, 'weapon_skin'),
            'emote' => $this->getEquippedItem($user, 'emote'),
            'banner' => $this->getEquippedItem($user, 'banner'),
            'title' => $this->getEquippedItem($user, 'title'),
        ];

        return response()->json(['loadout' => $loadout]);
    }

    /**
     * Get inventory statistics
     */
    public function stats()
    {
        $user = Auth::user();

        $stats = [
            'total_items' => $user->inventory()->count(),
            'character_skins' => $user->inventory()->byType('character_skin')->count(),
            'weapon_skins' => $user->inventory()->byType('weapon_skin')->count(),
            'emotes' => $user->inventory()->byType('emote')->count(),
            'banners' => $user->inventory()->byType('banner')->count(),
            'titles' => $user->inventory()->byType('title')->count(),
            'latest_unlock' => $user->inventory()->latest('unlocked_at')->first(),
        ];

        return response()->json(['stats' => $stats]);
    }

    /**
     * Batch equip items (loadout preset)
     */
    public function batchEquip(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.inventory_id' => 'required|exists:sa_user_inventory,id'
        ]);

        $user = Auth::user();

        foreach ($validated['items'] as $itemData) {
            $item = UserInventory::where('id', $itemData['inventory_id'])
                ->where('user_id', $user->id)
                ->first();

            if ($item) {
                $item->equip();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Loadout equipped successfully'
        ]);
    }
}

