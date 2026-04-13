<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_player_kills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('sa_matches')->onDelete('cascade');
            $table->foreignId('killer_id')->constrained('users');
            $table->foreignId('victim_id')->constrained('users');
            $table->foreignId('weapon_id')->nullable()->constrained('sa_weapons')->onDelete('set null');
            $table->decimal('distance', 10, 2)->nullable();
            $table->boolean('headshot')->default(false);
            $table->json('kill_position')->nullable();
            $table->timestamps();
            
            $table->index(['match_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_player_kills');
    }
};

