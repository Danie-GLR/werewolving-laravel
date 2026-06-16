<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GameController extends Controller
{
    // ── Bot names pool ────────────────────────────────────────────────────────

    private const BOT_NAMES = [
        'Shadow', 'Raven', 'Hunter', 'Blaze', 'Frost',
        'Viper', 'Ghost', 'Storm', 'Ember', 'Claw',
        'Dusk', 'Thorn', 'Sable', 'Flint', 'Hex',
    ];

    // ── Session helpers ───────────────────────────────────────────────────────

    private function sessionKey(int $gameId): string
    {
        return "player_id_{$gameId}";
    }

    private function sessionPlayer(Game $game): ?Player
    {
        $id = session($this->sessionKey($game->id));
        if (!$id) return null;
        return $game->players->firstWhere('id', $id);
    }

    // ── Game list / create ────────────────────────────────────────────────────

    public function index(): View
    {
        $games = Game::withCount('players')->latest()->get();
        return view('games.index', compact('games'));
    }

    public function create(): View
    {
        return view('games.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'min:2', 'max:60',
                'regex:/^[\w\s\-\'\.]+$/u',
            ],
        ], [
            'name.required' => 'Please give your game a name.',
            'name.min'      => 'The game name must be at least 2 characters.',
            'name.max'      => 'The game name cannot be longer than 60 characters.',
            'name.regex'    => 'The game name can only contain letters, numbers, spaces, and hyphens.',
        ]);

        $game = Game::create([
            'name'   => $data['name'],
            'status' => 'waiting',
            'phase'  => 'night',
            'round'  => 1,
        ]);

        session(["gm_{$game->id}" => true]);

        return redirect()->route('games.lobby', $game)
            ->with('success', 'Game created! Share this page with your players.');
    }

    // ── Edit / Update / Destroy ───────────────────────────────────────────────

    public function edit(Game $game): View
    {
        return view('games.edit', compact('game'));
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'min:2', 'max:60',
                'regex:/^[\w\s\-\'\.]+$/u',
            ],
        ], [
            'name.required' => 'Please enter a game name.',
            'name.min'      => 'The game name must be at least 2 characters.',
            'name.max'      => 'The game name cannot be longer than 60 characters.',
            'name.regex'    => 'The game name can only contain letters, numbers, spaces, and hyphens.',
        ]);

        $game->update(['name' => $data['name']]);

        return redirect()->route('games.show', $game)
            ->with('success', 'Game name updated.');
    }

    public function destroy(Game $game): RedirectResponse
    {
        if (!in_array($game->status, ['waiting', 'finished'])) {
            return redirect()->route('games.show', $game)
                ->with('error', 'You can only delete a game that is waiting or finished.');
        }

        $game->players()->delete();
        $game->delete();

        return redirect()->route('games.index')
            ->with('success', 'Game deleted.');
    }

    // ── Lobby ─────────────────────────────────────────────────────────────────

    public function lobby(Game $game): View
    {
        $game->load('players');
        $myPlayer = $this->sessionPlayer($game);
        $isGM     = session("gm_{$game->id}", false);

        return view('games.lobby', compact('game', 'myPlayer', 'isGM'));
    }

    /** GM joins as a named player */
    public function gmJoin(Request $request, Game $game): RedirectResponse
    {
        abort_unless(session("gm_{$game->id}"), 403);

        if ($game->status !== 'waiting') {
            return back()->with('error', 'The game has already started.');
        }

        if ($this->sessionPlayer($game)) {
            return redirect()->route('games.lobby', $game)
                ->with('error', 'You are already in this game as a player.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:30'],
        ], [
            'name.required' => 'Enter your name to join as a player.',
            'name.min'      => 'Your name must be at least 2 characters.',
        ]);

        $name = trim($data['name']);

        if ($game->players()->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return back()->with('error', "The name \"{$name}\" is already taken.");
        }

        $player = $game->players()->create([
            'name'     => $name,
            'is_alive' => true,
            'is_bot'   => false,
        ]);

        session([$this->sessionKey($game->id) => $player->id]);

        return redirect()->route('games.lobby', $game)
            ->with('success', "You joined as a player: {$name}.");
    }

    /** Player joins from their own device */
    public function join(Request $request, Game $game): RedirectResponse
    {
        if ($game->status !== 'waiting') {
            return back()->with('error', 'This game has already started.');
        }

        if ($this->sessionPlayer($game)) {
            return redirect()->route('games.lobby', $game);
        }

        $data = $request->validate([
            'name' => [
                'required', 'string', 'min:2', 'max:30',
                'regex:/^[\w\s\-\']+$/u',
            ],
        ], [
            'name.required' => 'Please enter your name to join.',
            'name.min'      => 'Your name must be at least 2 characters.',
            'name.max'      => 'Your name cannot be longer than 30 characters.',
            'name.regex'    => 'Your name can only contain letters, numbers, spaces, and hyphens.',
        ]);

        $name = trim($data['name']);

        if ($game->players()->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return back()
                ->withErrors(['name' => "The name \"{$name}\" is already taken. Pick a different one."])
                ->withInput();
        }

        if ($game->players()->count() >= 20) {
            return back()->with('error', 'This game is full (max 20 players).');
        }

        $player = $game->players()->create([
            'name'     => $name,
            'is_alive' => true,
            'is_bot'   => false,
        ]);

        session([$this->sessionKey($game->id) => $player->id]);

        return redirect()->route('games.lobby', $game)
            ->with('success', "Welcome, {$name}! Wait for the game master to start.");
    }

    // ── Bots ──────────────────────────────────────────────────────────────────

    /** Add a number of bots to the lobby */
    public function addBots(Request $request, Game $game): RedirectResponse
    {
        abort_unless(session("gm_{$game->id}"), 403);

        if ($game->status !== 'waiting') {
            return back()->with('error', 'You can only add bots while the game is waiting.');
        }

        $data = $request->validate([
            'bot_count' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $currentCount = $game->players()->count();
        $slotsLeft    = 20 - $currentCount;
        $toAdd        = min((int) $data['bot_count'], $slotsLeft);

        if ($toAdd <= 0) {
            return back()->with('error', 'The game is full. No bots could be added.');
        }

        // Pick names that aren't already used
        $usedNames = $game->players()->pluck('name')->map('strtolower')->toArray();
        $pool      = collect(self::BOT_NAMES)
            ->filter(fn($n) => !in_array(strtolower($n), $usedNames))
            ->shuffle()
            ->values();

        $added = 0;
        foreach ($pool->take($toAdd) as $name) {
            $game->players()->create([
                'name'     => $name . ' 🤖',
                'is_alive' => true,
                'is_bot'   => true,
            ]);
            $added++;
        }

        return redirect()->route('games.lobby', $game)
            ->with('success', "{$added} bot(s) added to the lobby.");
    }

    /** Remove all bots from the lobby */
    public function removeBots(Game $game): RedirectResponse
    {
        abort_unless(session("gm_{$game->id}"), 403);

        if ($game->status !== 'waiting') {
            return back()->with('error', 'You can only remove bots while the game is waiting.');
        }

        $removed = $game->players()->where('is_bot', true)->count();
        $game->players()->where('is_bot', true)->delete();

        return redirect()->route('games.lobby', $game)
            ->with('success', "{$removed} bot(s) removed.");
    }

    // ── Game master controls ──────────────────────────────────────────────────

    public function assignRoles(Request $request, Game $game): RedirectResponse
    {
        $game->load('players');
        $count = $game->players->count();

        if ($count < 4) {
            return back()->with('error', 'Need at least 4 players (or bots) to assign roles.');
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

        foreach ($game->players as $i => $player) {
            $player->update(['role' => $roles[$i]]);
        }

        $game->update(['status' => 'roles_assigned']);

        // Auto-resolve bot night/day actions for roles assigned phase
        $this->resolveBotActions($game);

        return redirect()->route('games.lobby', $game)
            ->with('success', 'Roles assigned! Players can now see their role.');
    }

    public function start(Request $request, Game $game): RedirectResponse
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

        // Immediately resolve all bot night actions
        $game->load('players');
        $this->resolveBotNightActions($game);

        return redirect()->route('games.play', $game)
            ->with('success', '🌙 Night falls… werewolves, choose your victim.');
    }

    // ── Bot AI logic ──────────────────────────────────────────────────────────

    /**
     * Bots auto-vote during the night phase.
     * Werewolf bots pick a random non-wolf alive player.
     * Doctor bots protect a random alive player.
     * Seer bots peek at a random alive non-self player.
     */
    private function resolveBotNightActions(Game $game): void
    {
        $game->load('players');
        $alive = $game->players->where('is_alive', true);

        foreach ($alive->where('is_bot', true) as $bot) {
            switch ($bot->role) {
                case 'Werewolf':
                    if ($bot->night_vote_target_id) break;
                    $targets = $alive->whereNotIn('role', ['Werewolf'])->values();
                    if ($targets->isEmpty()) break;
                    $target = $targets->random();
                    $bot->update(['night_vote_target_id' => $target->id]);
                    break;

                case 'Doctor':
                    if ($game->doctor_save_id) break;
                    $target = $alive->random();
                    $game->update(['doctor_save_id' => $target->id]);
                    break;

                case 'Seer':
                    if ($bot->has_peeked) break;
                    $targets = $alive->where('id', '!=', $bot->id)->values();
                    if ($targets->isEmpty()) break;
                    $target = $targets->random();
                    $game->update(['seer_peek_id' => $target->id]);
                    $bot->update(['has_peeked' => true]);
                    break;

                // Villager bots do nothing at night
            }
        }

        // Tally wolf votes now that bots have voted
        $game->load('players');
        if ($game->pendingWerewolfVotes() === 0) {
            $game->update(['night_kill_id' => $game->tallyNightVotes()]);
        }
    }

    /**
     * Bots auto-vote during the day phase — random alive non-self target.
     */
    private function resolveBotDayActions(Game $game): void
    {
        $game->load('players');
        $alive = $game->players->where('is_alive', true);

        foreach ($alive->where('is_bot', true) as $bot) {
            if ($bot->day_vote_target_id) continue;
            $targets = $alive->where('id', '!=', $bot->id)->values();
            if ($targets->isEmpty()) continue;
            $target = $targets->random();
            $bot->update(['day_vote_target_id' => $target->id]);
        }
    }

    /** Called after roles assigned to pre-fill bot state if needed */
    private function resolveBotActions(Game $game): void
    {
        // Nothing needed at roles_assigned phase — bots act when game starts
    }

    // ── Role reveal ───────────────────────────────────────────────────────────

    public function myRole(Game $game): View|RedirectResponse
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

    public function play(Game $game): View
    {
        $game->load('players');
        $myPlayer = $this->sessionPlayer($game);
        $isGM     = session("gm_{$game->id}", false);

        return view('games.play', compact('game', 'myPlayer', 'isGM'));
    }

    // ── Night actions ─────────────────────────────────────────────────────────

    public function nightVote(Request $request, Game $game): RedirectResponse
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

    public function doctorSave(Request $request, Game $game): RedirectResponse
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

    public function seerPeek(Request $request, Game $game): RedirectResponse
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

    public function resolveNight(Request $request, Game $game): RedirectResponse
    {
        abort_unless(session("gm_{$game->id}"), 403);

        $game->load('players');

        $killId = $game->night_kill_id;
        $saveId = $game->doctor_save_id;
        $killed = null;

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

        // Bots vote immediately at day start
        $this->resolveBotDayActions($game);

        $msg = $killed
            ? "☀️ Day breaks… {$killed->name} was killed in the night."
            : "☀️ Day breaks… nobody died! (Doctor saved someone)";

        return redirect()->route('games.play', $game)->with('success', $msg);
    }

    public function dayVote(Request $request, Game $game): RedirectResponse
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

    public function resolveDayManual(Request $request, Game $game): RedirectResponse
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

        // Bots act immediately at night start
        $this->resolveBotNightActions($game);

        $msg = $eliminated
            ? "🌙 Night falls… {$eliminated->name} was eliminated by the village."
            : "🌙 Night falls… the vote was tied — nobody was eliminated.";

        return redirect()->route('games.play', $game)->with('success', $msg);
    }

    // ── Show / legacy ─────────────────────────────────────────────────────────

    public function show(Game $game): View
    {
        $game->load('players');
        $isGM = session("gm_{$game->id}", false);
        return view('games.show', compact('game', 'isGM'));
    }
}
