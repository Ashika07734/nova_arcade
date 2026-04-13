<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_matches', function (Blueprint $table) {
            $table->id();
            $table->string('match_code', 10)->unique();
            $table->enum('game_mode', ['solo', 'duo', 'squad'])->default('solo');
            $table->integer('max_players')->default(50);
            $table->integer('current_players')->default(0);
            $table->enum('status', ['waiting', 'starting', 'in_progress', 'finished'])->default('waiting');
            $table->json('map_data')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'game_mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_matches');
    }
};