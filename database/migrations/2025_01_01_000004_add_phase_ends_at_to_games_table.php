<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('games', 'phase_ends_at')) {
            Schema::table('games', function (Blueprint $table) {
                // Timestamp the current phase (night / day-discussion / day-voting)
                // auto-expires at. Checked by play()/heartbeat() to drive
                // server-side auto-advance.
                $table->timestamp('phase_ends_at')->nullable()->after('day_subphase');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('games', 'phase_ends_at')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropColumn('phase_ends_at');
            });
        }
    }
};
