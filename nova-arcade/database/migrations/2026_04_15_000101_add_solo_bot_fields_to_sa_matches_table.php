<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sa_matches', function (Blueprint $table) {
            $table->string('mode', 20)->default('solo')->after('match_code');
            $table->string('difficulty', 20)->default('easy')->after('mode');
            $table->unsignedTinyInteger('bot_count')->default(3)->after('difficulty');
        });

        DB::table('sa_matches')->update([
            'mode' => DB::raw('game_mode'),
        ]);
    }

    public function down(): void
    {
        Schema::table('sa_matches', function (Blueprint $table) {
            $table->dropColumn(['mode', 'difficulty', 'bot_count']);
        });
    }
};
