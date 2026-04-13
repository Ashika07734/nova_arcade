<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_loot_spawns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('sa_matches')->onDelete('cascade');
            $table->enum('item_type', ['weapon', 'health', 'shield', 'ammo', 'armor', 'throwable']);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->json('position');
            $table->boolean('is_collected')->default(false);
            $table->foreignId('collected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('spawned_at')->useCurrent();
            $table->timestamp('collected_at')->nullable();
            
            $table->index(['match_id', 'is_collected']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_loot_spawns');
    }
};

