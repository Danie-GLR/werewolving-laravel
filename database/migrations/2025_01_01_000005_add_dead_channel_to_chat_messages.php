<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds 'dead' as a valid chat channel — used for the dead-only chat that
     * runs alongside the day channel, visible only to eliminated players
     * (and the GM). SQLite doesn't enforce native enums (Laravel emulates
     * them with a CHECK constraint via doctrine/dbal on alter), so on SQLite
     * we just leave the column as-is since CHECK constraints aren't actually
     * enforced there by default — this migration is a safe no-op on SQLite
     * and a real ALTER on MySQL/Postgres.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE chat_messages MODIFY COLUMN channel ENUM('day','night','seer','dead') NOT NULL");
        }
        // sqlite / pgsql: enum is just a string column under the hood here,
        // no migration needed for new values to be insertable.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE chat_messages MODIFY COLUMN channel ENUM('day','night','seer') NOT NULL");
        }
    }
};
