<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('period', ['all_time', 'weekly', 'monthly', 'seasonal']);
            $table->enum('category', ['wins', 'kills', 'kd_ratio', 'damage']);
            $table->integer('rank');
            $table->integer('score');
            $table->string('season', 20)->nullable();
            $table->timestamp('updated_at')->useCurrent();
            
            $table->unique(['user_id', 'period', 'category', 'season']);
            $table->index(['period', 'category', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_leaderboards');
    }
};

