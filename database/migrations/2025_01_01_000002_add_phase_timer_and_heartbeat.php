<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add last_seen_at to players (for disconnected indicator)
        // phase_ends_at on games was already added by a prior migration — skip it
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
