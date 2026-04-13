<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 50)->nullable()->unique()->after('name');
            }

            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('avatar');
            }
        });

        $users = DB::table('users')->select('id', 'name', 'username')->get();

        foreach ($users as $user) {
            if (!empty($user->username)) {
                continue;
            }

            $base = Str::slug($user->name ?: 'player', '_');
            $base = $base !== '' ? $base : 'player';
            $candidate = substr($base, 0, 40) . '_' . $user->id;

            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => $candidate]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'bio')) {
                $table->dropColumn('bio');
            }

            if (Schema::hasColumn('users', 'avatar')) {
                $table->dropColumn('avatar');
            }

            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique('users_username_unique');
                $table->dropColumn('username');
            }
        });
    }
};
