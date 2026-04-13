<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->integer('total_matches')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('top_5')->default(0);
            $table->integer('top_10')->default(0);
            $table->integer('kills')->default(0);
            $table->integer('deaths')->default(0);
            $table->decimal('kd_ratio', 10, 2)->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->integer('total_damage')->default(0);
            $table->integer('headshots')->default(0);
            $table->integer('longest_kill')->default(0);
            $table->integer('highest_kills_match')->default(0);
            $table->integer('total_playtime')->default(0);
            $table->foreignId('favorite_weapon_id')->nullable()->constrained('sa_weapons')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_user_stats');
    }
};

