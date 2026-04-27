<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sa_player_states', function (Blueprint $table) {
            $table->boolean('is_bot')->default(false)->after('user_id');
            $table->string('bot_name', 60)->nullable()->after('is_bot');
            $table->string('bot_difficulty', 20)->nullable()->after('bot_name');
            $table->foreignId('user_id')->nullable()->change();
            $table->index(['match_id', 'is_bot']);
        });
    }

    public function down(): void
    {
        Schema::table('sa_player_states', function (Blueprint $table) {
            $table->dropIndex(['match_id', 'is_bot']);
            $table->dropColumn(['is_bot', 'bot_name', 'bot_difficulty']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
