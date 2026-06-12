<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GameController extends Controller
{
    // ── Session helper ────────────────────────────────────────────────────────

    /** Session key that ties this browser to a player in this game */
    private function sessionKey(int $gameId): string
    {
        return "player_id_{$gameId}";
    }

    /** Get the Player for the current device in this game (or null) */
    private function sessionPlayer(Game $game): ?Player
    {
        $id = session($this->sessionKey($game->id));
        if (!$id) return null;
        return $game->players->firstWhere('id', $id);
    }

    // ── Game list / create ────────────────────────────────────────────────────

    public function index()
    {
        $games = Game::withCount('players')->latest()->get();
        return view('games.index', compact('games'));
    }

    public function create()
    {
        return view('games.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:60',
                'regex:/^[\w\s\-\'\.]+$/u',
            ],
        ], [
            'name.required' => 'Please give your game a name.',
            'name.min'      => 'The game name must be at least 2 characters.',
            'name.max'      => 'The game name cannot be longer than 60 characters.',
            'name.regex'    => 'The game name can only contain letters, numbers, spaces, hyphens, and apostrophes.',
        ]);

        $game = Game::create([
            'name'   => $validated['name'],
            'status' => 'waiting',
            'phase'  => 'night',
            'round'  => 0,
        ]);

        session(['gm_game_' . $game->id => true]);

        return redirect()->route('games.lobby', $game)
                         ->with('success', 'Game created! Share the lobby URL with your players.');
    }

    // ── Edit / Update / Destroy ───────────────────────────────────────────────

    public function edit(Game $game): View
    {
        return view('games.edit', compact('game'));
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:60',
                'regex:/^[\w\s\-\'\.]+$/u',
            ],
        ], [
            'name.required' => 'Please enter a game name.',
            'name.min'      => 'The game name must be at least 2 characters.',
            'name.max'      => 'The game name cannot be longer than 60 characters.',
            'name.regex'    => 'The game name can only contain letters, numbers, spaces, hyphens, and apostrophes.',
        ]);

        $game->update(['name' => $validated['name']]);

        return redirect()->route('games.show', $game)
                         ->with('success', 'Game name updated successfully.');
    }

    public function destroy(Game $game): RedirectResponse
    {
        // Only allow deleting games that are waiting or finished
        if (!in_array($game->status, ['waiting', 'finished'])) {
            return redirect()->route('games.show', $game)
                             ->with('error', 'You can only delete a game that is waiting to start or already finished.');
        }

        $game->players()->delete();
        $game->delete();

        return redirect()->route('games.index')
                         ->with('success', 'Game deleted successfully.');
    }

    // ── Lobby: players join from their own device ─────────────────────────────

    /** The shared lobby page everyone visits to join */
    public function lobby(Game $game)
    {
        $game->load('players');
        $myPlayer = $this->sessionPlayer($game);
        $isGM     = session("gm_{$game->id}", false);

        return view('games.lobby', compact('game', 'myPlayer', 'isGM'));
    }

    /** A device submits their name to join */
    public function join(Request $request, Game $game): RedirectResponse
    {
        if ($game->status !== 'waiting') {
            return redirect()->route('games.lobby', $game)
                             ->with('error', 'This game has already started. You cannot join now.');
        }

        if (session('player_' . $game->id)) {
            return redirect()->route('games.lobby', $game);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:30',
                'regex:/^[\w\s\-\']+$/u',
            ],
        ], [
            'name.required' => 'Please enter your name to join.',
            'name.min'      => 'Your name must be at least 2 characters.',
            'name.max'      => 'Your name cannot be longer than 30 characters.',
            'name.regex'    => 'Your name can only contain letters, numbers, spaces, and hyphens.',
        ]);

        // Check for duplicate name in this game
        $nameExists = $game->players()
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->exists();

        if ($nameExists) {
            return redirect()->route('games.lobby', $game)
                             ->withErrors(['name' => 'Someone with that name already joined. Please pick a different name.'])
                             ->withInput();
        }

        // Check player cap
        if ($game->players()->count() >= 20) {
            return redirect()->route('games.lobby', $game)
                             ->with('error', 'This game is full (max 20 players).');
        }

        $player = $game->players()->create([
            'name'     => $validated['name'],
            'is_alive' => true,
        ]);

        session(['player_' . $game->id => $player->id]);

        return redirect()->route('games.lobby', $game);
    }

    // ── Game master controls ──────────────────────────────────────────────────

    public function assignRoles(Request $request, Game $game)
    {
        $players = $game->players;
        $count   = $players->count();

        if ($count < 4) {
            return back()->with('error', 'Need at least 4 players to assign roles.');
        }

        $werewolfCount = max(1, (int) floor($count / 4));
        $seerCount     = $count >= 7 ? 1 : 0;
        $doctorCount   = $count >= 9 ? 1 : 0;
        $villagerCount = $count - $werewolfCount - $seerCount - $doctorCount;

        $roles = array_merge(
            array_fill(0, $werewolfCount, 'Werewolf'),
            array_fill(0, $seerCount,     'Seer'),
            array_fill(0, $doctorCount,   'Doctor'),
            array_fill(0, $villagerCount, 'Villager'),
        );

        shuffle($roles);

        foreach ($players as $i => $player) {
            $player->update(['role' => $roles[$i]]);
        }

        $game->update(['status' => 'roles_assigned']);

        return redirect()->route('games.lobby', $game)
            ->with('success', 'Roles assigned! Everyone can now see their role on their device.');
    }

    public function start(Request $request, Game $game)
    {
        if ($game->status === 'waiting') {
            return back()->with('error', 'Please assign roles first.');
        }

        $game->update([
            'status' => 'in_progress',
            'phase'  => 'night',
            'round'  => 1,
        ]);

        $game->players()->update([
            'night_vote_target_id' => null,
            'day_vote_target_id'   => null,
            'has_peeked'           => false,
        ]);

        return redirect()->route('games.play', $game)
            ->with('success', '🌙 Night falls… werewolves, choose your victim.');
    }

    // ── Role reveal (session-based, no token in URL) ──────────────────────────

    /** Each device sees their own role — identified purely by session */
    public function myRole(Game $game)
    {
        $game->load('players');
        $player = $this->sessionPlayer($game);

        if (!$player) {
            return redirect()->route('games.lobby', $game)
                ->with('error', 'Your session expired. Please rejoin.');
        }

        return view('games.my_role', compact('game', 'player'));
    }

    // ── Live game board ───────────────────────────────────────────────────────

    public function play(Game $game)
    {
        $game->load('players');
        $myPlayer = $this->sessionPlayer($game);
        $isGM     = session("gm_{$game->id}", false);

        return view('games.play', compact('game', 'myPlayer', 'isGM'));
    }

    // ── Night actions ─────────────────────────────────────────────────────────

    public function nightVote(Request $request, Game $game)
    {
        $request->validate(['target_id' => 'required|integer']);

        $game->load('players');
        $voter = $this->sessionPlayer($game);

        abort_unless($voter && $voter->role === 'Werewolf' && $voter->is_alive, 403);

        $target = $game->players()
            ->where('id', $request->target_id)
            ->where('is_alive', true)
            ->where('role', '!=', 'Werewolf')
            ->firstOrFail();

        $voter->update(['night_vote_target_id' => $target->id]);

        $game->load('players');
        if ($game->pendingWerewolfVotes() === 0) {
            $game->update(['night_kill_id' => $game->tallyNightVotes()]);
        }

        return redirect()->route('games.play', $game)
            ->with('success', '🐺 Vote cast.');
    }

    public function doctorSave(Request $request, Game $game)
    {
        $request->validate(['target_id' => 'required|integer']);

        $game->load('players');
        $doctor = $this->sessionPlayer($game);

        abort_unless($doctor && $doctor->role === 'Doctor' && $doctor->is_alive, 403);

        $target = $game->players()
            ->where('id', $request->target_id)
            ->where('is_alive', true)
            ->firstOrFail();

        $game->update(['doctor_save_id' => $target->id]);

        return redirect()->route('games.play', $game)
            ->with('success', '💊 Protection granted.');
    }

    public function seerPeek(Request $request, Game $game)
    {
        $request->validate(['target_id' => 'required|integer']);

        $game->load('players');
        $seer = $this->sessionPlayer($game);

        abort_unless($seer && $seer->role === 'Seer' && $seer->is_alive, 403);

        if ($seer->has_peeked) {
            return back()->with('error', 'You already peeked this round.');
        }

        $target = $game->players()
            ->where('id', $request->target_id)
            ->where('is_alive', true)
            ->where('id', '!=', $seer->id)
            ->firstOrFail();

        $game->update(['seer_peek_id' => $target->id]);
        $seer->update(['has_peeked' => true]);

        return redirect()->route('games.play', $game)
            ->with('peek_result', "🔮 {$target->name} is a {$target->role}!");
    }

    // ── Phase transitions ─────────────────────────────────────────────────────

    public function resolveNight(Request $request, Game $game)
    {
        abort_unless(session("gm_{$game->id}"), 403);

        $game->load('players');

        $killId  = $game->night_kill_id;
        $saveId  = $game->doctor_save_id;
        $killed  = null;

        if ($killId && $killId !== $saveId) {
            $killed = $game->players->firstWhere('id', $killId);
            $killed?->update(['is_alive' => false]);
        }

        $game->players()->update([
            'night_vote_target_id' => null,
            'day_vote_target_id'   => null,
            'has_peeked'           => false,
        ]);

        $game->update([
            'phase'          => 'day',
            'night_kill_id'  => null,
            'doctor_save_id' => null,
            'seer_peek_id'   => null,
        ]);

        $game->load('players');

        $winner = $game->checkWinner();
        if ($winner) {
            $game->update(['status' => 'finished']);
            $label = $winner === 'villagers' ? '🎉 Villagers win!' : '🐺 Werewolves win!';
            return redirect()->route('games.play', $game)->with('success', $label);
        }

        $msg = $killed
            ? "☀️ Day breaks… {$killed->name} was killed in the night."
            : "☀️ Day breaks… nobody died! (Doctor saved someone)";

        return redirect()->route('games.play', $game)->with('success', $msg);
    }

    public function dayVote(Request $request, Game $game)
    {
        $request->validate(['target_id' => 'required|integer']);

        $game->load('players');
        $voter = $this->sessionPlayer($game);

        abort_unless($voter && $voter->is_alive, 403);

        if ($game->phase !== 'day' || $game->status !== 'in_progress') {
            return back()->with('error', 'Voting is not open right now.');
        }

        $target = $game->players()
            ->where('id', $request->target_id)
            ->where('is_alive', true)
            ->where('id', '!=', $voter->id)
            ->firstOrFail();

        $voter->update(['day_vote_target_id' => $target->id]);

        $game->load('players');

        if ($game->pendingDayVotes() === 0) {
            return $this->resolveDay($game);
        }

        return redirect()->route('games.play', $game)
            ->with('success', "☀️ Voted for {$target->name}. Waiting for others…");
    }

    public function resolveDayManual(Request $request, Game $game)
    {
        abort_unless(session("gm_{$game->id}"), 403);
        return $this->resolveDay($game);
    }

    private function resolveDay(Game $game): RedirectResponse
    {
        $game->load('players');
        $eliminatedId = $game->tallyDayVotes();
        $eliminated   = null;

        if ($eliminatedId) {
            $eliminated = $game->players->firstWhere('id', $eliminatedId);
            $eliminated?->update(['is_alive' => false]);
        }

        $game->players()->update([
            'day_vote_target_id'   => null,
            'night_vote_target_id' => null,
            'has_peeked'           => false,
        ]);

        $game->update([
            'phase' => 'night',
            'round' => $game->round + 1,
        ]);

        $game->load('players');

        $winner = $game->checkWinner();
        if ($winner) {
            $game->update(['status' => 'finished']);
            $label = $winner === 'villagers' ? '🎉 Villagers win!' : '🐺 Werewolves win!';
            return redirect()->route('games.play', $game)->with('success', $label);
        }

        $msg = $eliminated
            ? "🌙 Night falls… {$eliminated->name} was eliminated by the village."
            : "🌙 Night falls… the vote was tied — nobody was eliminated.";

        return redirect()->route('games.play', $game)->with('success', $msg);
    }

    // ── Legacy show (redirects to lobby) ─────────────────────────────────────

    public function show(Game $game)
    {
        return redirect()->route('games.lobby', $game);
    }
}
