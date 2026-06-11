<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('phase')->default('night')->after('status'); // night | day
            $table->unsignedInteger('round')->default(1)->after('phase');
            // Pending night actions — store player IDs as nullable ints
            $table->unsignedBigInteger('night_kill_id')->nullable()->after('round');   // werewolf vote result
            $table->unsignedBigInteger('doctor_save_id')->nullable()->after('night_kill_id');
            $table->unsignedBigInteger('seer_peek_id')->nullable()->after('doctor_save_id');
        });

        Schema::table('players', function (Blueprint $table) {
            // Unique secret token — players use this to view their own role
            $table->string('token', 32)->nullable()->unique()->after('is_alive');
            // Track night votes cast (1 per player per night)
            $table->unsignedBigInteger('night_vote_target_id')->nullable()->after('token');
            $table->unsignedBigInteger('day_vote_target_id')->nullable()->after('night_vote_target_id');
            $table->boolean('has_peeked')->default(false)->after('day_vote_target_id');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['phase', 'round', 'night_kill_id', 'doctor_save_id', 'seer_peek_id']);
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['token', 'night_vote_target_id', 'day_vote_target_id', 'has_peeked']);
        });
    }
};
