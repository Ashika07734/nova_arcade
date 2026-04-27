<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sa_match_players', function (Blueprint $table) {
            $table->integer('score')->default(0)->after('xp_earned');
            $table->integer('shots_fired')->default(0)->after('score');
            $table->integer('shots_hit')->default(0)->after('shots_fired');
        });
    }

    public function down(): void
    {
        Schema::table('sa_match_players', function (Blueprint $table) {
            $table->dropColumn(['score', 'shots_fired', 'shots_hit']);
        });
    }
};
