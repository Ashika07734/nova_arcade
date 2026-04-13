<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_safe_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('sa_matches')->onDelete('cascade');
            $table->integer('phase');
            $table->json('center');
            $table->decimal('radius', 10, 2);
            $table->integer('damage_per_second')->default(5);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();
            
            $table->index(['match_id', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_safe_zones');
    }
};

