<?php

namespace App\Http\Controllers\SurvivalArena;

use App\Http\Controllers\Controller;
use App\Models\SurvivalArena\Weapon;
use Illuminate\Http\Request;

class WeaponController extends Controller
{
    /**
     * Get all weapons
     */
    public function index(Request $request)
    {
        $query = Weapon::query();

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Filter by rarity
        if ($request->has('rarity')) {
            $query->byRarity($request->rarity);
        }

        $weapons = $query->get()->map(function ($weapon) {
            return $weapon->toGameData();
        });

        return response()->json(['weapons' => $weapons]);
    }

    /**
     * Get single weapon
     */
    public function show(Weapon $weapon)
    {
        return response()->json(['weapon' => $weapon->toGameData()]);
    }

    /**
     * Get weapon stats comparison
     */
    public function compare(Request $request)
    {
        $validated = $request->validate([
            'weapon_ids' => 'required|array|min:2|max:4',
            'weapon_ids.*' => 'exists:sa_weapons,id'
        ]);

        $weapons = Weapon::whereIn('id', $validated['weapon_ids'])
            ->get()
            ->map(function ($weapon) {
                return $weapon->toGameData();
            });

        return response()->json(['weapons' => $weapons]);
    }

    /**
     * Get weapons by type
     */
    public function byType(string $type)
    {
        $weapons = Weapon::byType($type)
            ->get()
            ->map(function ($weapon) {
                return $weapon->toGameData();
            });

        return response()->json(['weapons' => $weapons]);
    }
}
