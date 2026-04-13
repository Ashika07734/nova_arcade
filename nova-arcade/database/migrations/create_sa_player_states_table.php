<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_player_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('sa_matches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('position');
            $table->json('rotation');
            $table->json('velocity')->nullable();
            $table->integer('health')->default(100);
            $table->integer('shield')->default(0);
            $table->integer('stamina')->default(100);
            $table->json('inventory')->nullable();
            $table->integer('active_weapon_slot')->default(0);
            $table->integer('ammo_current')->default(30);
            $table->integer('ammo_reserve')->default(120);
            $table->boolean('is_reloading')->default(false);
            $table->boolean('is_shooting')->default(false);
            $table->boolean('is_sprinting')->default(false);
            $table->boolean('is_crouching')->default(false);
            $table->timestamp('last_updated')->useCurrent();
            
            $table->unique(['match_id', 'user_id']);
            $table->index('last_updated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_player_states');
    }
};