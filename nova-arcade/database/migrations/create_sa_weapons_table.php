<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_weapons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['pistol', 'rifle', 'shotgun', 'sniper', 'smg', 'lmg']);
            $table->integer('damage')->default(25);
            $table->integer('fire_rate')->default(300);
            $table->integer('magazine_size')->default(30);
            $table->decimal('reload_time', 4, 2)->default(2.0);
            $table->integer('range')->default(100);
            $table->decimal('spread', 4, 3)->default(0.050);
            $table->decimal('headshot_multiplier', 3, 2)->default(2.0);
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary'])->default('common');
            $table->string('model_path')->nullable();
            $table->string('icon_path')->nullable();
            $table->string('sound_path')->nullable();
            $table->timestamps();
            
            $table->index('type');
            $table->index('rarity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_weapons');
    }
};

