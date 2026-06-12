<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    // ── Session helper ────────────────────────────────────────────────────────

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

    public function index()
    {
        $games = Game::withCount('players')->latest()->get();
        return view('games.index', compact('games'));
    }

    public function create()
    {
        return view('games.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
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

    // ── Lobby ─────────────────────────────────────────────────────────────────

    public function lobby(Game $game)
    {
        $game->load('players');
        $myPlayer = $this->sessionPlayer($game);
        $isGM     = session("gm_{$game->id}", false);

        return view('games.lobby', compact('game', 'myPlayer', 'isGM'));
    }

    public function join(Request $request, Game $game)
    {
        if ($game->status !== 'waiting') {
            return back()->with('error', 'This game has already started.');
        }

        if ($this->sessionPlayer($game)) {
            return redirect()->route('games.lobby', $game);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:40'],
        ]);

        $name = trim($request->name);

        if ($game->players()->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return back()->with('error', "The name \"{$name}\" is already taken in this game.");
        }

        $player = $game->players()->create([
            'name'     => $name,
            'role'     => null,
            'is_alive' => true,
        ]);

        session([$this->sessionKey($game->id) => $player->id]);

        return redirect()->route('games.lobby', $game)
            ->with('success', "Welcome, {$name}! Wait for the game master to start.");
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

    // ── Role reveal ───────────────────────────────────────────────────────────

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

    // ── Chat ──────────────────────────────────────────────────────────────────

    /**
     * GET /games/{game}/chat
     * Returns JSON messages for the current phase channel.
     * Night channel is restricted to werewolves only.
     */
    public function chatMessages(Request $request, Game $game): JsonResponse
    {
        $game->load('players');
        $player = $this->sessionPlayer($game);
        $isGM   = session("gm_{$game->id}", false);

        $channel = $game->phase; // 'day' or 'night'

        // Night: only werewolves (and GM as observer) may read
        if ($channel === 'night' && !$isGM) {
            if (!$player || $player->role !== 'Werewolf') {
                return response()->json(['messages' => [], 'locked' => true,
                    'reason' => 'The village sleeps… 🌙']);
            }
        }

        $messages = ChatMessage::where('game_id', $game->id)
            ->where('channel', $channel)
            ->where('round', $game->round)
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => [
                'id'      => $m->id,
                'name'    => $m->player_name,
                'message' => $m->message,
                'time'    => $m->created_at->format('H:i'),
            ]);

        return response()->json([
            'messages' => $messages,
            'locked'   => false,
            'channel'  => $channel,
            'round'    => $game->round,
        ]);
    }

    /**
     * POST /games/{game}/chat
     * Stores a chat message, enforcing per-channel access rules.
     */
    public function sendChat(Request $request, Game $game): JsonResponse
    {
        $game->load('players');
        $player = $this->sessionPlayer($game);
        $isGM   = session("gm_{$game->id}", false);

        if (!$player && !$isGM) {
            return response()->json(['error' => 'Not authorised.'], 403);
        }

        $channel = $game->phase;

        // Night: only alive werewolves may write
        if ($channel === 'night') {
            if (!$player || $player->role !== 'Werewolf' || !$player->is_alive) {
                return response()->json(['error' => 'Only werewolves can chat at night.'], 403);
            }
        }

        // Day: dead players cannot chat
        if ($channel === 'day' && $player && !$player->is_alive) {
            return response()->json(['error' => 'Eliminated players cannot chat.'], 403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:300'],
        ]);

        $authorName = $player ? $player->name : '🎭 GM';

        ChatMessage::create([
            'game_id'     => $game->id,
            'player_id'   => $player?->id ?? 0,
            'player_name' => $authorName,
            'channel'     => $channel,
            'round'       => $game->round,
            'message'     => $validated['message'],
        ]);

        return response()->json(['ok' => true]);
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

    private function resolveDay(Game $game): \Illuminate\Http\RedirectResponse
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

    // ── Legacy show ───────────────────────────────────────────────────────────

    public function show(Game $game)
    {
        return redirect()->route('games.lobby', $game);
    }
}
