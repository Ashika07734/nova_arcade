<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description');
            $table->string('icon')->nullable();
            $table->string('type', 50);
            $table->json('requirement');
            $table->integer('reward_xp')->default(1000);
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');
            $table->timestamps();
        });
        
        Schema::create('sa_user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained('sa_achievements')->onDelete('cascade');
            $table->integer('progress')->default(0);
            $table->boolean('unlocked')->default(false);
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'achievement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_user_achievements');
        Schema::dropIfExists('sa_achievements');
    }
};

