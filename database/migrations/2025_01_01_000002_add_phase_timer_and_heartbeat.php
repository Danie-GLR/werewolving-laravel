<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // When the current phase automatically ends (null = no auto-advance)
            $table->timestamp('phase_ends_at')->nullable()->after('round');
        });

        Schema::table('players', function (Blueprint $table) {
            // Last heartbeat ping — used to show (away) indicator
            $table->timestamp('last_seen_at')->nullable()->after('has_peeked');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('phase_ends_at');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });
    }
};
