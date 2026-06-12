<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('player_name');       // denormalised for speed
            $table->enum('channel', ['day', 'night']); // day = all players, night = werewolves only
            $table->unsignedInteger('round');
            $table->text('message');
            $table->timestamps();

            $table->index(['game_id', 'channel', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
