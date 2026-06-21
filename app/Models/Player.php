<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Player extends Model
{
    protected $fillable = [
        'game_id', 'name', 'role', 'is_alive', 'is_bot', 'last_seen_at',
        'token', 'night_vote_target_id', 'day_vote_target_id', 'has_peeked',
        'seat_number',
    ];

    protected $casts = [
        'is_alive'     => 'boolean',
        'is_bot'       => 'boolean',
        'has_peeked'   => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function getRoleIconAttribute(): string
    {
        return match ($this->role) {
            'Werewolf' => '🐺',
            'Seer'     => '🔮',
            'Doctor'   => '💊',
            'Villager' => '👨‍🌾',
            default    => '❓',
        };
    }

    public function generateToken(): void
    {
        $this->update(['token' => Str::random(32)]);
    }

    public function roleRevealUrl(): string
    {
        return route('games.my-role', ['game' => $this->game_id, 'token' => $this->token]);
    }
}
