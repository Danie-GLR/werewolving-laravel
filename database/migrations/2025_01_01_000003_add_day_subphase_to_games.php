<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('games', 'day_subphase')) {
            Schema::table('games', function (Blueprint $table) {
                // 'discussion' = chat open, no voting
                // 'voting'     = chat closed, vote buttons shown
                // null         = night phase (not applicable)
                $table->string('day_subphase')->nullable()->after('phase');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('games', 'day_subphase')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropColumn('day_subphase');
            });
        }
    }
};
