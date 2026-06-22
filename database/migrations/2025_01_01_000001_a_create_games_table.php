<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('waiting'); // waiting, roles_assigned, in_progress, finished
            $table->string('phase')->default('night');    // night | day
            $table->unsignedInteger('round')->default(1);
            $table->unsignedBigInteger('night_kill_id')->nullable();
            $table->unsignedBigInteger('doctor_save_id')->nullable();
            $table->unsignedBigInteger('seer_peek_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
