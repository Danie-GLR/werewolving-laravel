<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role')->nullable(); // Werewolf, Villager, Seer, Doctor
            $table->boolean('is_alive')->default(true);
            $table->boolean('is_bot')->default(false);
            $table->string('token')->nullable();
            $table->unsignedBigInteger('night_vote_target_id')->nullable();
            $table->unsignedBigInteger('day_vote_target_id')->nullable();
            $table->boolean('has_peeked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
