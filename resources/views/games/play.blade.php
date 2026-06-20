@extends('layouts.app')

@section('title', $game->name)

@section('content')
<style>
    /* ── Full-page day/night theme ── */
    body {
        transition: background-color .8s ease, color .4s ease;
    }

    /* Night theme (default dark) — no override needed, uses existing vars */

    /* Day theme — warm light wash over everything */
    body.phase-is-day {
        background-color: #fdf6e3 !important;
        color: #2d2a1e !important;
    }

    body.phase-is-day var(--bg2),
    body.phase-is-day .stat,
    body.phase-is-day .action-section,
    body.phase-is-day .player-card,
    body.phase-is-day .chat-panel,
    body.phase-is-day .gm-bar {
        background: rgba(255,255,255,0.6) !important;
        border-color: rgba(217,119,6,0.2) !important;
    }

    body.phase-is-day .stat,
    body.phase-is-day .action-section,
    body.phase-is-day .player-card,
    body.phase-is-day .chat-panel,
    body.phase-is-day .gm-bar {
        background: rgba(255,255,255,0.7);
        border-color: rgba(217,119,6,0.25);
        color: #2d2a1e;
    }

    body.phase-is-day .pn,
    body.phase-is-day .phase-sub,
    body.phase-is-day .tally-note,
    body.phase-is-day .chat-header,
    body.phase-is-day .section-title {
        color: #7a6540 !important;
    }

    body.phase-is-day .chat-input-row input {
        background: #fff8ed;
        border-color: rgba(217,119,6,0.3);
        color: #2d2a1e;
    }

    body.phase-is-day .chat-msg { color: #3d3520; }
    body.phase-is-day .chat-msg.system { color: #9a845a; }

    /* ── Phase header ── */
    .phase-header {
        text-align: center;
        padding: 1.5rem 1rem 1.2rem;
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
        transition: background .8s ease, border-color .8s ease;
    }

    .phase-night { background: rgba(79,70,229,0.12);   border: 1px solid rgba(79,70,229,0.3); }
    .phase-day   { background: rgba(255,209,102,0.18); border: 1px solid rgba(217,119,6,0.4); }
    .phase-fin   { background: rgba(45,206,137,0.08);  border: 1px solid rgba(45,206,137,0.25); }

    .phase-label { font-size: 1.6rem; font-weight: 900; }
    .phase-sub   { color: var(--muted); margin-top: .25rem; font-size: .9rem; font-weight: 600; }

    /* ── Countdown timer — big, always visible ── */
    .phase-timer {
        display: inline-block;
        font-size: 2.4rem;
        font-weight: 900;
        margin-top: .6rem;
        letter-spacing: .06em;
        font-variant-numeric: tabular-nums;
        min-width: 110px;
    }

    .phase-night .phase-timer { color: #a5b4fc; }  /* soft indigo for night */
    .phase-day   .phase-timer { color: #d97706; }  /* amber for day */

    .timer-low { color: var(--red) !important; animation: pulse .8s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

    .timer-label {
        display: block;
        font-size: .7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .1em;
        opacity: .55;
        margin-top: .1rem;
    }

    /* ── Stats row ── */
    .stats { display: flex; gap: .75rem; margin-bottom: 1.2rem; flex-wrap: wrap; }

    .stat {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: .8rem 1rem;
        flex: 1;
        min-width: 75px;
        text-align: center;
    }

    .stat .value { font-size: 1.6rem; font-weight: 900; }
    .stat .label { font-size: .7rem; color: var(--muted); margin-top: .1rem; font-weight: 700; text-transform: uppercase; }

    /* ── Role card ── */
    .role-card {
        border-radius: var(--radius);
        padding: 1.8rem 1.5rem;
        text-align: center;
        margin-bottom: 1.2rem;
        position: relative;
        overflow: hidden;
    }

    .role-Werewolf { background: rgba(233,69,96,0.1);   border: 2px solid rgba(233,69,96,0.35); }
    .role-Villager { background: rgba(45,206,137,0.08); border: 2px solid rgba(45,206,137,0.3); }
    .role-Seer     { background: rgba(168,85,247,0.1);  border: 2px solid rgba(168,85,247,0.3); }
    .role-Doctor   { background: rgba(59,130,246,0.08); border: 2px solid rgba(59,130,246,0.3); }

    .role-card-img {
        width: 100px; height: 100px;
        object-fit: contain;
        filter: drop-shadow(0 6px 20px rgba(0,0,0,0.5));
        margin-bottom: .75rem;
    }

    .role-title { font-size: 1.3rem; font-weight: 900; margin-bottom: .5rem; }
    .role-desc  { color: var(--muted); font-size: .9rem; line-height: 1.6; font-weight: 600; }

    /* ── Action section ── */
    .action-section {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.2rem 1.5rem;
        margin-bottom: 1rem;
    }

    .action-section h2 { margin-top: 0; font-size: 1.05rem; font-weight: 900; margin-bottom: 1rem; }

    .vote-grid { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; }

    .vote-btn {
        background: var(--bg3);
        border: 1.5px solid var(--border);
        color: var(--text);
        padding: .5rem 1rem;
        border-radius: 30px;
        cursor: pointer;
        font-size: .9rem;
        font-family: 'Nunito', sans-serif;
        font-weight: 700;
        transition: all .2s;
    }

    .vote-btn:hover { background: var(--pink); border-color: var(--pink); transform: translateY(-1px); }
    .vote-btn.voted { background: var(--green); border-color: var(--green); cursor: default; }

    /* ── Player grid ── */
    .player-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: .6rem;
        margin: 1rem 0;
    }

    .player-card {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: .9rem .6rem;
        text-align: center;
        transition: border-color .2s;
        position: relative;
    }

    .player-card:hover { border-color: rgba(255,61,127,0.3); }
    .player-card.dead  { opacity: .35; }

    .player-card .av { font-size: 1.6rem; margin-bottom: .35rem; }
    .player-card .av img { width: 40px; height: 40px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4)); }
    .player-card .pn { font-size: .8rem; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* disconnected badge */
    .away-badge {
        font-size: .6rem;
        color: var(--muted);
        font-weight: 700;
        display: block;
        margin-top: .15rem;
        opacity: .7;
    }

    .you-dot {
        width: 6px; height: 6px;
        background: var(--pink);
        border-radius: 50%;
        display: inline-block;
        margin-left: 4px;
        vertical-align: middle;
    }

    .role-chip {
        font-size: .68rem;
        padding: .15rem .5rem;
        border-radius: 10px;
        display: inline-block;
        margin-top: .3rem;
        font-weight: 800;
    }

    .role-chip.role-Werewolf { background: rgba(233,69,96,0.2);   color: #fca5a5; }
    .role-chip.role-Villager { background: rgba(45,206,137,0.15); color: #86efac; }
    .role-chip.role-Seer     { background: rgba(168,85,247,0.15); color: #d8b4fe; }
    .role-chip.role-Doctor   { background: rgba(59,130,246,0.15); color: #93c5fd; }

    /* ── GM bar ── */
    .gm-bar {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1rem 1.2rem;
        margin-bottom: 1rem;
        display: flex;
        gap: .75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .tally-note { font-size: .82rem; color: var(--muted); font-weight: 600; }

    /* ── Peek alert ── */
    .alert-peek {
        background: rgba(168,85,247,0.1);
        border: 1px solid rgba(168,85,247,0.3);
        border-radius: var(--radius-sm);
        padding: .75rem 1rem;
        color: #e9d5ff;
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .section-title {
        font-size: .8rem;
        font-weight: 900;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: .75rem;
        margin-top: 1.5rem;
    }

    /* ── Chat ── */
    .chat-panel {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-top: 1.5rem;
        overflow: hidden;
    }

    .chat-header {
        padding: .75rem 1rem;
        font-size: .82rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--muted);
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .chat-messages {
        height: 220px;
        overflow-y: auto;
        padding: .75rem 1rem;
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }

    .chat-msg {
        font-size: .84rem;
        font-weight: 600;
        line-height: 1.45;
    }

    .chat-msg .sender {
        font-weight: 900;
        margin-right: .35rem;
    }

    .chat-msg.system   { color: var(--muted); font-style: italic; }
    .chat-msg.wolf     { color: #fca5a5; }
    .chat-msg.seer-msg { color: #e9d5ff; }

    .chat-msg .role-icon {
        width: 18px; height: 18px;
        object-fit: contain;
        vertical-align: middle;
        margin-left: .25rem;
        filter: drop-shadow(0 1px 3px rgba(0,0,0,0.5));
    }

    .chat-input-row {
        display: flex;
        gap: .5rem;
        padding: .75rem 1rem;
        border-top: 1px solid var(--border);
    }

    .chat-input-row input {
        flex: 1;
        background: var(--bg3);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: .45rem .9rem;
        color: var(--text);
        font-family: 'Nunito', sans-serif;
        font-size: .88rem;
        font-weight: 600;
        outline: none;
    }

    .chat-input-row input:focus { border-color: var(--pink); }

    .chat-send-btn {
        background: var(--pink);
        border: none;
        border-radius: 20px;
        color: #fff;
        padding: .45rem .9rem;
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
        font-size: .85rem;
        cursor: pointer;
        transition: opacity .2s;
    }

    .chat-send-btn:hover { opacity: .85; }

    .chat-msg.system-announce {
        text-align: center;
        font-size: .78rem;
        font-weight: 700;
        color: var(--yellow);
        background: rgba(255,209,102,0.08);
        border: 1px solid rgba(255,209,102,0.2);
        border-radius: var(--radius-sm);
        padding: .4rem .7rem;
        margin: .25rem 0;
        align-self: center;
    }

    .chat-msg.system-announce strong { color: var(--pink); }
</style>

@php
    if (!function_exists('roleImg')) {
        function roleImg($role) {
            $map = [
                'Werewolf' => 'icon_werewolf.png',
                'Villager' => 'icon_villager.png',
                'Seer'     => 'icon_seer.png',
                'Doctor'   => 'icon_doctor.png',
            ];
            return asset('images/' . ($map[$role] ?? 'icon_villager.png'));
        }
    }
@endphp

@if(session('peek_result'))
    <div class="alert-peek">{{ session('peek_result') }}</div>
@endif

@php
    $alive   = $game->players->where('is_alive', true);
    $wolves  = $alive->where('role','Werewolf')->count();
    $village = $alive->whereNotIn('role',['Werewolf'])->count();
    $phase   = $game->phase;
    $status  = $game->status;

    // Disconnected threshold: 20 seconds without a heartbeat
    $disconnectThreshold = now()->subSeconds(20);

    // Day sub-phase
    $daySubphase = $game->day_subphase; // 'discussion' | 'voting' | null
@endphp

{{-- Apply day/night body class immediately so there's no flash --}}
<script>
    document.body.classList.add('{{ $phase === "day" && $status === "in_progress" ? "phase-is-day" : "phase-is-night" }}');
</script>

{{-- Phase header with countdown --}}
@if($status === 'finished')
    @php $winner = $game->checkWinner() ?? ($wolves === 0 ? 'villagers' : 'werewolves'); @endphp
    <div class="phase-header phase-fin">
        <div class="phase-label">{{ $winner === 'villagers' ? '🎉 Villagers Win!' : '🐺 Werewolves Win!' }}</div>
        <div class="phase-sub">{{ $game->name }} · Game over after {{ $game->round }} round(s)</div>
    </div>
@else
    <div class="phase-header {{ $phase === 'night' ? 'phase-night' : 'phase-day' }}">
        @if($phase === 'night')
            <div class="phase-label">🌙 Night — Round {{ $game->round }}</div>
        @elseif($daySubphase === 'discussion')
            <div class="phase-label">☀️ Day — Round {{ $game->round }}</div>
            <div class="phase-sub" style="font-size:1rem; font-weight:900; color:#d97706; margin-top:.3rem;">💬 Discussion Phase</div>
        @else
            <div class="phase-label">☀️ Day — Round {{ $game->round }}</div>
            <div class="phase-sub" style="font-size:1rem; font-weight:900; color:#ef4444; margin-top:.3rem;">🗳️ Voting Phase</div>
        @endif
        <div class="phase-sub">{{ $game->name }}</div>
        @if($game->phase_ends_at && $status === 'in_progress')
            <div class="phase-timer" id="phase-timer">--:--<span class="timer-label">time remaining</span></div>
        @endif
    </div>
@endif

{{-- Role card removed — player grid below conveys this info --}}

{{-- Night actions --}}
@if($myPlayer && $myPlayer->is_alive && $status === 'in_progress' && $phase === 'night')

    @if($myPlayer->role === 'Werewolf')
        <div class="action-section">
            <h2>🐺 Choose tonight's victim</h2>
            @if($myPlayer->night_vote_target_id)
                @php $vt = $game->players->firstWhere('id', $myPlayer->night_vote_target_id); @endphp
                <p style="color:var(--green); font-weight:700;">✅ You voted for <strong>{{ $vt?->name }}</strong>.</p>
                @php $fellowWolves = $game->players->where('role','Werewolf')->where('is_alive',true)->where('id','!=',$myPlayer->id); @endphp
                @if($fellowWolves->count())
                    <p style="color:#fca5a5; font-size:.82rem; font-weight:600; margin-top:.5rem;">Fellow wolves: {{ $fellowWolves->pluck('name')->join(', ') }}</p>
                @endif
            @else
                @php $fellowWolves = $game->players->where('role','Werewolf')->where('is_alive',true)->where('id','!=',$myPlayer->id); @endphp
                @if($fellowWolves->count())
                    <p style="color:#fca5a5; font-size:.82rem; font-weight:600; margin-bottom:.5rem;">Fellow wolves: {{ $fellowWolves->pluck('name')->join(', ') }}</p>
                @endif
                <p style="color:var(--muted); font-size:.85rem; font-weight:600;">Pick a non-wolf target:</p>
                <div class="vote-grid">
                    @foreach($alive->whereNotIn('role',['Werewolf']) as $t)
                        <form method="POST" action="{{ route('games.night-vote', $game) }}">
                            @csrf
                            <input type="hidden" name="target_id" value="{{ $t->id }}">
                            <button type="submit" class="vote-btn">{{ $t->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($myPlayer->role === 'Doctor')
        <div class="action-section">
            <h2>💊 Protect someone tonight</h2>
            @if($game->doctor_save_id)
                @php $sv = $game->players->firstWhere('id', $game->doctor_save_id); @endphp
                <p style="color:var(--green); font-weight:700;">✅ Protecting <strong>{{ $sv?->name }}</strong> tonight.</p>
            @else
                <div class="vote-grid">
                    @foreach($alive as $t)
                        <form method="POST" action="{{ route('games.doctor-save', $game) }}">
                            @csrf
                            <input type="hidden" name="target_id" value="{{ $t->id }}">
                            <button type="submit" class="vote-btn">{{ $t->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($myPlayer->role === 'Seer')
        <div class="action-section">
            <h2>🔮 Peek at someone's role</h2>
            @if($myPlayer->has_peeked)
                <p style="color:var(--green); font-weight:700;">✅ You've used your vision this round.</p>
            @else
                <div class="vote-grid">
                    @foreach($alive->where('id','!=',$myPlayer->id) as $t)
                        <form method="POST" action="{{ route('games.seer-peek', $game) }}">
                            @csrf
                            <input type="hidden" name="target_id" value="{{ $t->id }}">
                            <button type="submit" class="vote-btn">{{ $t->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

{{-- Day actions --}}
@elseif($myPlayer && $myPlayer->is_alive && $status === 'in_progress' && $phase === 'day' && $daySubphase === 'discussion')
    <div class="action-section" style="text-align:center; padding:1.5rem;">
        <div style="font-size:2rem; margin-bottom:.5rem;">💬</div>
        <p style="font-weight:800; font-size:1rem; margin-bottom:.3rem;">Discussion Phase</p>
        <p style="color:var(--muted); font-size:.85rem; font-weight:600;">Talk it out in the chat below. Voting opens when the timer ends.</p>
    </div>

@elseif($myPlayer && $myPlayer->is_alive && $status === 'in_progress' && $phase === 'day' && $daySubphase === 'voting')
    <div class="action-section">
        <h2>🗳️ Vote to eliminate a suspect</h2>
        @if($myPlayer->day_vote_target_id)
            @php $dv = $game->players->firstWhere('id', $myPlayer->day_vote_target_id); @endphp
            <p style="color:var(--green); font-weight:700;">✅ You voted for <strong>{{ $dv?->name }}</strong>. Waiting for others…</p>
        @else
            <p style="color:var(--muted); font-size:.85rem; font-weight:600;">Who do you think is a werewolf?</p>
            <div class="vote-grid">
                @foreach($alive->where('id','!=',$myPlayer->id) as $t)
                    <form method="POST" action="{{ route('games.day-vote', $game) }}">
                        @csrf
                        <input type="hidden" name="target_id" value="{{ $t->id }}">
                        <button type="submit" class="vote-btn">{{ $t->name }}</button>
                    </form>
                @endforeach
            </div>
        @endif
    </div>

@elseif($myPlayer && !$myPlayer->is_alive && $status === 'in_progress')
    <div class="action-section" style="text-align:center; padding:2rem;">
        <div style="font-size:2.5rem; margin-bottom:.5rem;">☠️</div>
        <p style="color:var(--red); font-weight:800;">You've been eliminated.</p>
        <p style="color:var(--muted); font-size:.85rem; font-weight:600; margin-top:.3rem;">Spectate the rest of the game.</p>
    </div>
@endif

{{-- Player grid --}}
{{--
    Role visibility rules:
    - Finished game: show everyone's real role icon + chip
    - Eliminated players: always reveal their real role (death reveal)
    - Your own card: show your real role icon
    - Werewolf viewing another wolf: show wolf icon
    - Seer viewing a peeked player (this round): show their real icon (visual hint)
    - Everyone else (alive, unrevealed): show 👤 — NO role information
    - GM and spectators: same as "everyone else" for alive players — they cannot see roles early
--}}
<div class="section-title">Players</div>
<div class="player-grid">
    @foreach($game->players as $p)
        @php
            $isMe     = $myPlayer && $myPlayer->id === $p->id;
            $isFellow = $myPlayer && $myPlayer->role === 'Werewolf' && $p->role === 'Werewolf' && !$isMe;

            // Seer: show icon only for the player peeked THIS round
            $seerPeeked = $myPlayer
                && $myPlayer->role === 'Seer'
                && $game->seer_peek_id == $p->id
                && $p->is_alive;

            $showRealIcon = $status === 'finished' || $isMe || $isFellow || $seerPeeked || !$p->is_alive;

            // Disconnected: no heartbeat in last 20 seconds (bots are always "online")
            $isAway = !$p->is_bot
                && $p->last_seen_at
                && $p->last_seen_at->lt($disconnectThreshold);
        @endphp
        <div class="player-card {{ !$p->is_alive ? 'dead' : '' }}">
            <div class="av">
                @if($showRealIcon)
                    <img src="{{ roleImg($p->role) }}" alt="{{ $p->role }}">
                @else
                    {{ $p->is_alive ? '👤' : '💀' }}
                @endif
            </div>
            <div class="pn">
                {{ $p->name }}
                @if($isMe)
                    <span class="you-dot" title="you"></span>
                @endif
            </div>
            @if($status === 'finished' || !$p->is_alive)
                <span class="role-chip role-{{ $p->role }}">{{ $p->role }}</span>
            @elseif($seerPeeked)
                {{-- Seer only: show the role chip for the peeked player --}}
                <span class="role-chip role-{{ $p->role }}">{{ $p->role }}</span>
            @endif
            @if(!$p->is_alive)
                <div style="font-size:.65rem; color:var(--red); margin-top:.2rem; font-weight:800;">☠️ Out</div>
            @endif
            @if($isAway && $p->is_alive)
                <span class="away-badge">(disconnected)</span>
            @endif
        </div>
    @endforeach
</div>
{{-- Chat (includes village history / system announcements inline) --}}
@if($status === 'in_progress' || $status === 'finished')
@php
    // Determine chat channel label for this viewer
    $canChat = false;
    $chatLabel = '💬 Day Chat';

    if ($myPlayer && $myPlayer->is_alive && $status === 'in_progress') {
        if ($phase === 'night' && $myPlayer->role === 'Werewolf') {
            $canChat   = true;
            $chatLabel = '🐺 Wolf Pack (night only)';
        } elseif ($phase === 'day' && $daySubphase === 'discussion') {
            $canChat   = true;
            $chatLabel = $myPlayer->role === 'Seer'
                ? '💬 Discussion · 🔮 Seer Visions'
                : '💬 Discussion';
        } elseif ($phase === 'day' && $daySubphase === 'voting') {
            $canChat   = true;
            $chatLabel = $myPlayer->role === 'Seer'
                ? '💬 Chat · 🗳️ Voting open · 🔮 Seer Visions'
                : '💬 Chat · 🗳️ Voting open';
        } elseif ($phase === 'night' && $myPlayer->role !== 'Werewolf') {
            $canChat   = false;
            $chatLabel = '💬 Chat (day only)';
        }
    }
@endphp
<div class="chat-panel">
    <div class="chat-header">{{ $chatLabel }}</div>
    <div class="chat-messages" id="chat-scroll">
        @forelse($chatMessages as $cm)
            @php
                $isSystem   = $cm->player_name === '📢 Game';
                $isSeerMsg  = $cm->channel === 'seer';
                $isWolfMsg  = $cm->channel === 'night';

                // Parse seer messages: format is "text|icon:Role"
                $msgText = $cm->message;
                $msgIcon = null;
                if ($isSeerMsg && str_contains($cm->message, '|icon:')) {
                    [$msgText, $iconPart] = explode('|icon:', $cm->message, 2);
                    $msgIcon = trim($iconPart);
                }
                // Convert **bold** markdown to <strong>
                $msgText = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($msgText));
            @endphp
            <div class="chat-msg {{ $isSystem ? 'system-announce' : ($isSeerMsg ? 'seer-msg' : ($isWolfMsg ? 'wolf' : '')) }}" data-id="{{ $cm->id }}">
                @if($isSystem)
                    {!! $msgText !!}
                @else
                    <span class="sender">{{ $cm->player_name }}:</span>
                    {!! $msgText !!}
                    @if($msgIcon)
                        <img src="{{ roleImg($msgIcon) }}" alt="{{ $msgIcon }}" class="role-icon">
                    @endif
                    @if($isWolfMsg)
                        <span style="font-size:.7rem; opacity:.5; margin-left:.3rem;">(wolf chat)</span>
                    @endif
                @endif
            </div>
        @empty
            <div class="chat-msg system">No messages yet…</div>
        @endforelse
    </div>
    @if($canChat)
        <form method="POST" action="{{ route('games.chat', $game) }}" class="chat-input-row" id="chat-form">
            @csrf
            <input type="text" name="message" placeholder="Press enter to send" maxlength="300" autocomplete="off" id="chat-input">
            <button type="submit" class="chat-send-btn">➤</button>
        </form>
    @elseif(!$myPlayer)
        <div style="padding:.6rem 1rem; font-size:.8rem; color:var(--muted); font-weight:600;">Spectating — chat is read-only.</div>
    @elseif(!$myPlayer->is_alive)
        <div style="padding:.6rem 1rem; font-size:.8rem; color:var(--muted); font-weight:600;">You've been eliminated — chat is read-only.</div>
    @else
        <div style="padding:.6rem 1rem; font-size:.8rem; color:var(--muted); font-weight:600;">Chat opens during the day.</div>
    @endif
</div>
@endif

{{-- Page no longer hard-refreshes on a fixed timer. The heartbeat below
     polls quietly every 10s and only reloads when the phase/round/status
     actually changes server-side, so nothing visibly flickers in between. --}}

{{-- ── JS: countdown timer + heartbeat ── --}}
<script>
(function () {
    const phaseEndsAt   = @json($game->phase_ends_at?->toIso8601String());
    const currentPhase  = @json($phase);
    const currentSub    = @json($daySubphase);
    const currentRound  = @json($game->round);
    const currentStatus = @json($status);

    // ── Countdown (only if there's an active phase timer) ──────────────────
    if (phaseEndsAt) {
        const timerEl = document.getElementById('phase-timer');
        const endsAt  = new Date(phaseEndsAt);
        let reloaded  = false;

        function tick() {
            const diff = Math.max(0, Math.floor((endsAt - Date.now()) / 1000));
            const mm   = String(Math.floor(diff / 60)).padStart(2, '0');
            const ss   = String(diff % 60).padStart(2, '0');

            if (timerEl) {
                timerEl.childNodes[0].textContent = `${mm}:${ss}`;
                timerEl.classList.toggle('timer-low', diff <= 10);
            }

            if (diff <= 0 && !reloaded) {
                reloaded = true;
                document.body.style.transition = 'opacity .25s ease';
                document.body.style.opacity = '0';
                setTimeout(() => window.location.reload(), 200);
            }
        }

        tick();
        setInterval(tick, 1000);
    }

    // ── Heartbeat ────────────────────────────────────────────────────────────
    // Pings the server every 8 s — for every viewer, not just seated players —
    // so the game keeps advancing server-side even if a tab's own countdown
    // never fires (backgrounded tab, clock drift, etc). Reload only happens
    // when something has actually changed, so this stays invisible otherwise.
    if (currentStatus === 'in_progress') {
        setInterval(function () {
            fetch("{{ route('games.heartbeat', $game) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            })
            .then(res => res.json())
            .then(data => {
                if (
                    data.phase !== currentPhase ||
                    data.day_subphase !== currentSub ||
                    data.round !== currentRound ||
                    data.status !== currentStatus
                ) {
                    document.body.style.transition = 'opacity .25s ease';
                    document.body.style.opacity = '0';
                    setTimeout(() => window.location.reload(), 200);
                }
            })
            .catch(() => {});
        }, 8000);
    }

    // ── Chat: send + poll via AJAX, no page reload ──────────────────────────
    const chatScroll = document.getElementById('chat-scroll');
    const chatForm    = document.getElementById('chat-form');
    const chatInput   = document.getElementById('chat-input');
    const chatFetchUrl = @json(route('games.chat.fetch', $game));
    const chatSendUrl  = @json(route('games.chat', $game));
    const csrfToken    = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function isNearBottom() {
        if (!chatScroll) return true;
        return chatScroll.scrollHeight - chatScroll.scrollTop - chatScroll.clientHeight < 80;
    }

    function scrollToBottom() {
        if (chatScroll) chatScroll.scrollTop = chatScroll.scrollHeight;
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function boldify(str) {
        return escHtml(str).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    }

    function renderMessage(m) {
        const div = document.createElement('div');
        const isWolf = m.channel === 'night';
        const isSeer = m.channel === 'seer';
        div.className = 'chat-msg ' + (m.isSystem ? 'system-announce' : (isSeer ? 'seer-msg' : (isWolf ? 'wolf' : '')));
        div.dataset.id = m.id;

        if (m.isSystem) {
            div.innerHTML = boldify(m.message);
        } else {
            let body = `<span class="sender">${escHtml(m.name)}:</span> ${boldify(m.message)}`;
            if (isWolf) body += `<span style="font-size:.7rem;opacity:.5;margin-left:.3rem;">(wolf chat)</span>`;
            div.innerHTML = body;
        }
        return div;
    }

    function appendNewMessages(messages) {
        if (!chatScroll) return;
        const existingIds = new Set(
            Array.from(chatScroll.querySelectorAll('[data-id]')).map(el => el.dataset.id)
        );
        const placeholder = chatScroll.querySelector('.chat-msg.system:not([data-id])');
        const wasNearBottom = isNearBottom();
        let appended = false;

        messages.forEach(m => {
            if (existingIds.has(String(m.id))) return;
            if (placeholder) placeholder.remove();
            chatScroll.appendChild(renderMessage(m));
            appended = true;
        });

        if (appended && wasNearBottom) scrollToBottom();
    }

    // Initial scroll to bottom on page load
    scrollToBottom();

    // Poll for new messages every 4s — quiet, no reload, appends only what's new
    if (chatScroll) {
        setInterval(function () {
            fetch(chatFetchUrl, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => appendNewMessages(data.messages || []))
                .catch(() => {});
        }, 4000);
    }

    // Send a message via AJAX — stays on the page, no scroll jump
    if (chatForm && chatInput) {
        chatForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const text = chatInput.value.trim();
            if (!text) return;

            chatInput.disabled = true;

            fetch(chatSendUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ message: text }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    chatInput.value = '';
                    // Immediately pull fresh messages so the sender sees their own message
                    fetch(chatFetchUrl, { headers: { 'Accept': 'application/json' } })
                        .then(res => res.json())
                        .then(d => appendNewMessages(d.messages || []));
                }
            })
            .catch(() => {})
            .finally(() => {
                chatInput.disabled = false;
                chatInput.focus();
            });
        });
    }
})();
</script>

@endsection
