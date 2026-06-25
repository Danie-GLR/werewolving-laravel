<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GameController extends Controller
{
    // ── Phase duration (seconds) ───────────────────────────────────────────────
    private const NIGHT_DURATION      = 30;  // 30 seconds for night
    private const DAY_DISCUSS_DURATION = 60; // 1 minute for day discussion
    private const DAY_VOTE_DURATION    = 30;  // 30 seconds for voting

    // ── Bot names pool ────────────────────────────────────────────────────────
    private const BOT_NAMES = [
        'Shadow', 'Jason Voorhees', 'William Afton', 'Blaze', 'John Kaisen',
        'Viper', 'Tze', 'Storm', 'john Zenin', 'Claw',
        'Dusk', 'Thorn', 'Sable', 'Gojo', 'Geto',
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

        if ($game->players()->count() >= 20) {
            return back()->with('error', 'This game is full (max 20 players).');
        }

        $player = $game->players()->create([
            'name'     => $name,
            'is_alive' => true,
            'is_bot'   => false,
            'last_seen_at' => now(),
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
            'name'         => $name,
            'is_alive'     => true,
            'is_bot'       => false,
            'last_seen_at' => now(),
        ]);

        session([$this->sessionKey($game->id) => $player->id]);

        return redirect()->route('games.lobby', $game)
            ->with('success', "Welcome, {$name}! Wait for the game master to start.");
    }

    // ── Bots ──────────────────────────────────────────────────────────────────

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

    /**
     * Assign roles AND immediately start the game in one step.
     * The GM hits one button — roles are assigned and the game starts.
     * Players still on the lobby page are auto-redirected by the meta refresh.
     */
    public function assignRoles(Request $request, Game $game): RedirectResponse
    {
        abort_unless(session("gm_{$game->id}"), 403);

        $game->load('players');
        $count = $game->players->count();

        if ($count < 4) {
            return back()->with('error', 'Need at least 4 players (or bots) to start.');
        }

        // ── Assign roles ──────────────────────────────────────────────────────
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
            $player->update([
                'role' => $roles[$i],
                // Keep existing numbers stable on re-assign; only number
                // players who don't already have one.
                'seat_number' => $player->seat_number ?? ($i + 1),
            ]);
            // Generate a unique token so players can access their private role
            // reveal URL. generateToken() was defined but never called before.
            if (!$player->token) {
                $player->generateToken();
            }
        }

        // ── Start immediately ─────────────────────────────────────────────────
        $game->update([
            'status'        => 'in_progress',
            'phase'         => 'night',
            'day_subphase'  => null,
            'round'         => 1,
            'phase_ends_at' => now()->addSeconds(self::NIGHT_DURATION),
        ]);

        $game->players()->update([
            'night_vote_target_id' => null,
            'day_vote_target_id'   => null,
            'has_peeked'           => false,
        ]);

        $game->load('players');
        $this->resolveBotNightActions($game);

        return redirect()->route('games.play', $game)
            ->with('success', '🌙 Night falls — game started!');
    }

    /**
     * Kept for route compatibility — just delegates to assignRoles or
     * redirects to play if the game is already running.
     */
    public function start(Request $request, Game $game): RedirectResponse
    {
        abort_unless(session("gm_{$game->id}"), 403);

        if ($game->status === 'in_progress') {
            return redirect()->route('games.play', $game);
        }

        return $this->assignRoles($request, $game);
    }

    // ── Role reveal ───────────────────────────────────────────────────────────

    public function myRole(Game $game, Request $request): View|RedirectResponse
    {
        $game->load('players');

        // Allow access via session (the player on this device) OR via the
        // token in the URL (share link generated from the GM show page).
        $player = $this->sessionPlayer($game);

        if (!$player && $request->filled('token')) {
            $player = $game->players->firstWhere('token', $request->input('token'));
        }

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

        // Auto-advance if the phase timer has expired
        if ($game->status === 'in_progress' && $game->phase_ends_at && now()->gt($game->phase_ends_at)) {
            $this->autoAdvancePhase($game);
            $game->refresh();
            $game->load('players');
        }

        // Load chat messages visible to this viewer
        $chatMessages = $this->getChatMessages($game, $myPlayer);

        // Build rich vote arrays (same shape as fetchVotes JSON) so the blade
        // can render voter name/number → target name directly.
        $names   = $game->players->pluck('name', 'id');
        $numbers = $game->players->pluck('seat_number', 'id');

        $buildVotes = fn($raw) => collect($raw)->map(fn ($targetId, $voterId) => [
            'voterId'     => (int) $voterId,
            'voterName'   => $names->get($voterId),
            'voterNumber' => $numbers->get($voterId),
            'targetId'    => $targetId,
            'targetName'  => $names->get($targetId),
        ])->values()->all();

        $dayVotes   = $buildVotes($game->dayVoteBreakdown());
        $nightVotes = $buildVotes($game->nightVoteBreakdown());

        return view('games.play', compact('game', 'myPlayer', 'isGM', 'chatMessages', 'dayVotes', 'nightVotes'));
    }

    /**
     * Lightweight polling endpoint — returns current day-vote (and, for
     * werewolves, night-vote) breakdowns as JSON so the UI can update vote
     * tallies live without a full page reload.
     */
    public function fetchVotes(Game $game): JsonResponse
    {
        $game->load('players');
        $myPlayer = $this->sessionPlayer($game);

        $names = $game->players->pluck('name', 'id');
        $numbers = $game->players->pluck('seat_number', 'id');

        $dayVotes = collect($game->dayVoteBreakdown())->map(fn ($targetId, $voterId) => [
            'voterId'     => (int) $voterId,
            'voterName'   => $names->get($voterId),
            'voterNumber' => $numbers->get($voterId),
            'targetId'    => $targetId,
            'targetName'  => $names->get($targetId),
        ])->values();

        $payload = ['dayVotes' => $dayVotes];

        // Night votes only ever included in the payload for werewolves —
        // never leak wolf voting to anyone else, even via this endpoint.
        if ($myPlayer && $myPlayer->role === 'Werewolf' && $myPlayer->is_alive) {
            $payload['nightVotes'] = collect($game->nightVoteBreakdown())->map(fn ($targetId, $voterId) => [
                'voterId'     => (int) $voterId,
                'voterName'   => $names->get($voterId),
                'voterNumber' => $numbers->get($voterId),
                'targetId'    => $targetId,
                'targetName'  => $names->get($targetId),
            ])->values();
        }

        return response()->json($payload);
    }

    // ── Heartbeat (called by JS every 10 seconds) ─────────────────────────────

    public function heartbeat(Request $request, Game $game): JsonResponse
    {
        $game->load('players');
        $player = $this->sessionPlayer($game);

        if ($player) {
            $player->update(['last_seen_at' => now()]);
        }

        // Auto-advance if the phase timer has expired — heartbeat fires every
        // 10s for every connected client, so this guarantees the game keeps
        // moving even if nobody's browser happens to reload the page.
        if ($game->status === 'in_progress' && $game->phase_ends_at && now()->gt($game->phase_ends_at)) {
            $this->autoAdvancePhase($game);
            $game->refresh();
        }

        // Return minimal state for the JS timer
        return response()->json([
            'phase_ends_at' => $game->phase_ends_at?->toIso8601String(),
            'phase'         => $game->phase,
            'day_subphase'  => $game->day_subphase,
            'round'         => $game->round,
            'status'        => $game->status,
        ]);
    }

    // ── Auto-advance phase ────────────────────────────────────────────────────

    private function autoAdvancePhase(Game $game): void
    {
        if ($game->phase === 'night') {
            $this->performNightResolution($game);
        } elseif ($game->day_subphase === 'discussion') {
            // Discussion ended — move to voting
            $game->update([
                'day_subphase'  => 'voting',
                'phase_ends_at' => now()->addSeconds(self::DAY_VOTE_DURATION),
            ]);
            // Bots vote immediately when voting opens
            $this->resolveBotDayActions($game);
        } else {
            // Voting ended — resolve the day
            $this->performDayResolution($game);
        }
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

    /**
     * Seer peeks at a player.
     * Writes a 'seer' channel chat message so the result persists across refreshes.
     */
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

        // Write a seer-channel chat message so the result is visible only to the Seer
        // across future refreshes for this round, and includes the role icon hint.
        ChatMessage::create([
            'game_id'     => $game->id,
            'player_id'   => $seer->id,
            'player_name' => $seer->name,
            'channel'     => 'seer',
            'round'       => $game->round,
            'message'     => "🔮 Your vision reveals: **{$target->name}** is a **{$target->role}**!|icon:{$target->role}",
        ]);

        return redirect()->route('games.play', $game)
            ->with('peek_result', "🔮 {$target->name} is a {$target->role}!");
    }

    // ── Chat ──────────────────────────────────────────────────────────────────

    public function sendChat(Request $request, Game $game): RedirectResponse|JsonResponse
    {
        $game->load('players');
        $player = $this->sessionPlayer($game);

        if (!$player || $game->status !== 'in_progress') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'You cannot chat right now.'], 422);
            }
            return back()->with('error', 'You cannot chat right now.');
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:300'],
        ]);

        // Determine channel
        if (!$player->is_alive) {
            // Eliminated players can always talk to each other, any phase.
            $channel = 'dead';
        } elseif ($game->phase === 'night' && $player->role === 'Werewolf') {
            $channel = 'night';
        } elseif ($game->phase === 'day' && in_array($game->day_subphase, ['discussion', 'voting'])) {
            $channel = 'day';
        } else {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Chat is closed right now.'], 422);
            }
            return back()->with('error', 'Chat is closed right now.');
        }

        $msg = ChatMessage::create([
            'game_id'     => $game->id,
            'player_id'   => $player->id,
            'player_name' => $player->name,
            'channel'     => $channel,
            'round'       => $game->round,
            'message'     => trim($data['message']),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $msg->id]);
        }

        return redirect()->route('games.play', $game);
    }

    /**
     * Lightweight polling endpoint — returns chat messages visible to the
     * current viewer as JSON, without re-rendering the whole page.
     */
    public function fetchChat(Game $game): JsonResponse
    {
        $game->load('players');
        $myPlayer = $this->sessionPlayer($game);
        $messages = $this->getChatMessages($game, $myPlayer);

        // Map player_id => seat_number once, instead of querying per message
        $seatNumbers = $game->players->pluck('seat_number', 'id');

        return response()->json([
            'messages' => $messages->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->player_name,
                'number'   => $seatNumbers->get($m->player_id),
                'message'  => $m->message,
                'channel'  => $m->channel,
                'isMine'   => $myPlayer && $m->player_id === $myPlayer->id,
                'isSystem' => $m->player_name === '📢 Game',
            ])->values(),
        ]);
    }

    /**
     * Return chat messages visible to this viewer:
     * - day messages: everyone
     * - night messages: only werewolves
     * - seer messages: only the seer (their own peek results)
     * - dead messages: only eliminated players (talking among themselves)
     */
    private function getChatMessages(Game $game, ?Player $myPlayer): \Illuminate\Support\Collection
    {
        if (!$myPlayer) {
            // Spectator: only see day chat
            return ChatMessage::where('game_id', $game->id)
                ->where('channel', 'day')
                ->orderBy('created_at')
                ->get();
        }

        $channels = ['day'];

        if ($myPlayer->role === 'Werewolf') {
            $channels[] = 'night';
        }

        if ($myPlayer->role === 'Seer') {
            $channels[] = 'seer';
        }

        if (!$myPlayer->is_alive) {
            $channels[] = 'dead';
        }

        return ChatMessage::where('game_id', $game->id)
            ->whereIn('channel', $channels)
            ->orderBy('created_at')
            ->get();
    }

    // ── Phase transitions ─────────────────────────────────────────────────────

    /** GM can force-skip discussion and jump straight to voting */
    private function performNightResolution(Game $game): void
    {
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
            'day_subphase'   => 'discussion',
            'night_kill_id'  => null,
            'doctor_save_id' => null,
            'seer_peek_id'   => null,
            'phase_ends_at'  => now()->addSeconds(self::DAY_DISCUSS_DURATION),
        ]);

        $game->load('players');

        // Write a day-channel system message announcing the night result (always,
        // even on the game-ending round so players see the outcome).
        $msg = $killed
            ? " Day breaks… **{$killed->name}** was killed in the night."
            : " Day breaks… nobody died! (Doctor saved someone)";

        ChatMessage::create([
            'game_id'     => $game->id,
            'player_id'   => null,  // system message — no real player
            'player_name' => '📢 Game',
            'channel'     => 'day',
            'round'       => $game->round,
            'message'     => $msg,
        ]);

        if ($game->checkWinner()) {
            $game->update(['status' => 'finished', 'phase_ends_at' => null]);
            return;
        }

        $this->resolveBotDayActions($game);
    }

    public function dayVote(Request $request, Game $game): RedirectResponse
    {
        $request->validate(['target_id' => 'required|integer']);

        $game->load('players');
        $voter = $this->sessionPlayer($game);

        abort_unless($voter && $voter->is_alive, 403);

        if ($game->phase !== 'day' || $game->day_subphase !== 'voting' || $game->status !== 'in_progress') {
            return back()->with('error', 'Voting is not open right now.');
        }

        $target = $game->players()
            ->where('id', $request->target_id)
            ->where('is_alive', true)
            ->where('id', '!=', $voter->id)
            ->firstOrFail();

        $voter->update(['day_vote_target_id' => $target->id]);

        return redirect()->route('games.play', $game)
            ->with('success', "☀️ Voted for {$target->name}. Waiting for others…");
    }

    private function performDayResolution(Game $game): void
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

        // Capture the current round number BEFORE incrementing, so the
        // system message is stamped on the round that was just resolved.
        $currentRound = $game->round;

        $game->update([
            'phase'         => 'night',
            'day_subphase'  => null,
            'round'         => $game->round + 1,
            'phase_ends_at' => now()->addSeconds(self::NIGHT_DURATION),
        ]);

        $game->load('players');

        // Write a day-channel system message announcing the day result (always,
        // even on the game-ending round so players see who was eliminated).
        $msg = $eliminated
            ? " Night falls… **{$eliminated->name}** was eliminated by the village."
            : " Night falls… the vote was tied — nobody was eliminated.";

        ChatMessage::create([
            'game_id'     => $game->id,
            'player_id'   => null,  // system message — no real player
            'player_name' => '📢 Game',
            'channel'     => 'day', // day channel so everyone sees the outcome
            'round'       => $currentRound,  // the round just resolved, not the new one
            'message'     => $msg,
        ]);

        if ($game->checkWinner()) {
            $game->update(['status' => 'finished', 'phase_ends_at' => null]);
            return;
        }

        $this->resolveBotNightActions($game);
    }

    // ── Bot AI logic ──────────────────────────────────────────────────────────

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
            }
        }

        $game->load('players');
        if ($game->pendingWerewolfVotes() === 0) {
            $game->update(['night_kill_id' => $game->tallyNightVotes()]);
        }
    }

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

    // ── Show / legacy ─────────────────────────────────────────────────────────

    public function show(Game $game): View
    {
        $game->load('players');
        $isGM = session("gm_{$game->id}", false);
        return view('games.show', compact('game', 'isGM'));
    }
}
