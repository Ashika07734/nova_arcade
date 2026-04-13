<?php

namespace Database\Seeders\SurvivalArena;

use App\Models\SurvivalArena\Weapon;
use Illuminate\Database\Seeder;

class WeaponSeeder extends Seeder
{
    public function run(): void
    {
        $weapons = [
            [
                'name' => 'Pistol',
                'slug' => 'pistol',
                'type' => 'pistol',
                'damage' => 15,
                'fire_rate' => 300,
                'magazine_size' => 12,
                'reload_time' => 1.5,
                'range' => 50,
                'spread' => 0.05,
                'headshot_multiplier' => 2.0,
                'rarity' => 'common'
            ],
            [
                'name' => 'Assault Rifle',
                'slug' => 'assault-rifle',
                'type' => 'rifle',
                'damage' => 25,
                'fire_rate' => 150,
                'magazine_size' => 30,
                'reload_time' => 2.5,
                'range' => 100,
                'spread' => 0.03,
                'headshot_multiplier' => 2.0,
                'rarity' => 'uncommon'
            ],
            [
                'name' => 'Shotgun',
                'slug' => 'shotgun',
                'type' => 'shotgun',
                'damage' => 80,
                'fire_rate' => 1000,
                'magazine_size' => 6,
                'reload_time' => 3.0,
                'range' => 20,
                'spread' => 0.15,
                'headshot_multiplier' => 1.5,
                'rarity' => 'rare'
            ],
            [
                'name' => 'Sniper Rifle',
                'slug' => 'sniper-rifle',
                'type' => 'sniper',
                'damage' => 100,
                'fire_rate' => 1500,
                'magazine_size' => 5,
                'reload_time' => 3.5,
                'range' => 200,
                'spread' => 0.01,
                'headshot_multiplier' => 3.0,
                'rarity' => 'epic'
            ],
            [
                'name' => 'SMG',
                'slug' => 'smg',
                'type' => 'smg',
                'damage' => 18,
                'fire_rate' => 100,
                'magazine_size' => 25,
                'reload_time' => 2.0,
                'range' => 60,
                'spread' => 0.06,
                'headshot_multiplier' => 1.8,
                'rarity' => 'uncommon'
            ]
        ];

        foreach ($weapons as $weapon) {
            Weapon::create($weapon);
        }
    }
}