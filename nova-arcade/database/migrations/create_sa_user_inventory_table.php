<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sa_user_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('item_type', ['character_skin', 'weapon_skin', 'emote', 'banner', 'title']);
            $table->unsignedBigInteger('item_id');
            $table->boolean('equipped')->default(false);
            $table->timestamp('unlocked_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['user_id', 'item_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sa_user_inventory');
    }
};

