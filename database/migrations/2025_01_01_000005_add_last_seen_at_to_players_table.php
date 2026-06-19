<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add last_seen_at to players (used for the "disconnected" indicator
        // in the play view). Renamed/reordered to 000005 so it always runs
        // AFTER 2025_01_01_000002_create_players_table — the original
        // 000002 timestamp collided alphabetically with create_players_table
        // and ran first on fresh databases, silently no-opping because the
        // players table didn't exist yet (Schema::hasTable guard was false).
        if (Schema::hasTable('players') && !Schema::hasColumn('players', 'last_seen_at')) {
            Schema::table('players', function (Blueprint $table) {
                $table->timestamp('last_seen_at')->nullable()->after('has_peeked');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('players', 'last_seen_at')) {
            Schema::table('players', function (Blueprint $table) {
                $table->dropColumn('last_seen_at');
            });
        }
    }
};
