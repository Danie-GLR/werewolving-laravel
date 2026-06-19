<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $fillable = [
        'name', 'status', 'phase', 'round', 'day_subphase',
        'night_kill_id', 'doctor_save_id', 'seer_peek_id',
        'phase_ends_at', 'discussion_ends_at',
    ];

    protected $casts = [
        'phase_ends_at'      => 'datetime',
        'discussion_ends_at' => 'datetime',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'waiting'        => '⏳ Waiting',
            'roles_assigned' => '🎭 Roles Assigned',
            'in_progress'    => '🐺 In Progress',
            'finished'       => '✅ Finished',
            default          => ucfirst($this->status),
        };
    }

    // ── Win condition ─────────────────────────────────────────────────────────

    /**
     * Check if the game is over. Returns 'werewolves', 'villagers', or null.
     */
    public function checkWinner(): ?string
    {
        $alive    = $this->players->where('is_alive', true);
        $wolves   = $alive->where('role', 'Werewolf')->count();
        $villagers = $alive->whereNotIn('role', ['Werewolf'])->count();

        if ($wolves === 0) {
            return 'villagers';
        }

        // Werewolves win when they equal or outnumber non-wolves
        if ($wolves >= $villagers) {
            return 'werewolves';
        }

        return null;
    }

    // ── Night action helpers ──────────────────────────────────────────────────

    public function alivePlayers()
    {
        return $this->players->where('is_alive', true);
    }

    public function aliveWerewolves()
    {
        return $this->alivePlayers()->where('role', 'Werewolf');
    }

    /**
     * How many werewolves still need to cast a night vote this round?
     */
    public function pendingWerewolfVotes(): int
    {
        return $this->aliveWerewolves()
            ->whereNull('night_vote_target_id')
            ->count();
    }

    /**
     * Tally werewolf night votes — return the most-voted target id, or null if tied/missing.
     */
    public function tallyNightVotes(): ?int
    {
        $votes = $this->aliveWerewolves()
            ->pluck('night_vote_target_id')
            ->filter()
            ->countBy()
            ->sortDesc();

        if ($votes->isEmpty()) return null;

        $max   = $votes->first();
        $top   = $votes->filter(fn($c) => $c === $max);

        // Tied votes: no kill
        if ($top->count() > 1) return null;

        return (int) $top->keys()->first();
    }

    /**
     * How many alive players still need to cast a day vote?
     */
    public function pendingDayVotes(): int
    {
        return $this->alivePlayers()
            ->whereNull('day_vote_target_id')
            ->count();
    }

    /**
     * Tally day votes — return the most-voted player id, or null if tied.
     */
    public function tallyDayVotes(): ?int
    {
        $votes = $this->alivePlayers()
            ->pluck('day_vote_target_id')
            ->filter()
            ->countBy()
            ->sortDesc();

        if ($votes->isEmpty()) return null;

        $max = $votes->first();
        $top = $votes->filter(fn($c) => $c === $max);

        if ($top->count() > 1) return null;

        return (int) $top->keys()->first();
    }
}
