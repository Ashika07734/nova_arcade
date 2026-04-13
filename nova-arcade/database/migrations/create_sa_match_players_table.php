<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_match_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('sa_matches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->nullable();
            $table->integer('kills')->default(0);
            $table->integer('deaths')->default(0);
            $table->integer('damage_dealt')->default(0);
            $table->integer('damage_taken')->default(0);
            $table->integer('headshots')->default(0);
            $table->integer('placement')->nullable();
            $table->integer('xp_earned')->default(0);
            $table->integer('survival_time')->default(0);
            $table->json('final_position')->nullable();
            $table->boolean('is_alive')->default(true);
            $table->timestamp('joined_at');
            $table->timestamp('died_at')->nullable();
            $table->timestamps();
            
            $table->unique(['match_id', 'user_id']);
            $table->index(['match_id', 'is_alive']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_match_players');
    }
};

