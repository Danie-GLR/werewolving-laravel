<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('players', 'seat_number')) {
            Schema::table('players', function (Blueprint $table) {
                // Permanent player number (#1, #2...) assigned when roles are
                // dealt. Stable for the whole game — used in chat, votes, grid.
                $table->unsignedSmallInteger('seat_number')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('players', 'seat_number')) {
            Schema::table('players', function (Blueprint $table) {
                $table->dropColumn('seat_number');
            });
        }
    }
};
