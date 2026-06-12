@extends('layouts.app')

@section('title', 'Lobby — ' . $game->name)

@section('content')
<style>
    /* ── Lobby hero ── */
    .lobby-hero {
        text-align: center;
        padding: 2.5rem 1rem 2rem;
    }

    .lobby-game-name {
        font-size: clamp(1.8rem, 5vw, 2.8rem);
        font-weight: 900;
        letter-spacing: -.5px;
        margin-bottom: .5rem;
    }

    .lobby-hint {
        color: var(--muted);
        font-size: .85rem;
        font-weight: 600;
        margin-bottom: .75rem;
    }

    /* ── Status pill ── */
    .status-pill {
        display: inline-block;
        padding: .3rem 1rem;
        border-radius: 20px;
        font-size: .8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .pill-waiting  { background: rgba(155,135,184,0.15); color: var(--muted);   border: 1px solid rgba(155,135,184,0.2); }
    .pill-assigned { background: rgba(168,85,247,0.15);  color: #c084fc;        border: 1px solid rgba(168,85,247,0.25); }
    .pill-progress { background: rgba(255,209,102,0.15); color: var(--yellow);  border: 1px solid rgba(255,209,102,0.25); }

    /* ── Join / status cards ── */
    .lobby-card {
        max-width: 440px;
        margin: 0 auto 1.2rem;
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
    }

    .lobby-card h2 {
        font-size: 1.15rem;
        font-weight: 900;
        margin-bottom: 1rem;
    }

    .form-group { margin-bottom: 1rem; }

    .form-group label {
        display: block;
        margin-bottom: .4rem;
        font-size: .78rem;
        font-weight: 800;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .form-group input {
        width: 100%;
        background: var(--bg);
        border: 1.5px solid var(--border);
        color: var(--text);
        padding: .65rem 1rem;
        border-radius: var(--radius-sm);
        font-size: 1rem;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        transition: border-color .2s;
    }

    .form-group input:focus { outline: none; border-color: var(--pink); }
    .form-group input::placeholder { color: var(--muted); }
    .form-error { color: #fca5a5; font-size: .8rem; margin-top: .3rem; font-weight: 700; }

    .status-icon { font-size: 2.8rem; margin-bottom: .75rem; display: block; text-align: center; }

    /* ── Player list ── */
    .player-list {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.2rem 1.5rem;
        margin-bottom: 1rem;
    }

    .player-list-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .player-list-title {
        font-size: 1rem;
        font-weight: 900;
    }

    .player-count-badge {
        background: rgba(255,61,127,0.15);
        color: var(--pink);
        font-weight: 800;
        font-size: .8rem;
        padding: .2rem .7rem;
        border-radius: 20px;
    }

    .player-row {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .6rem 0;
        border-bottom: 1px solid var(--border);
    }

    .player-row:last-child { border-bottom: none; }

    .player-avatar {
        width: 38px;
        height: 38px;
        background: var(--bg3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 900;
        color: var(--pink);
        flex-shrink: 0;
        border: 2px solid rgba(255,61,127,0.2);
    }

    .player-name-text { font-weight: 800; font-size: .95rem; }

    .you-tag {
        font-size: .65rem;
        font-weight: 800;
        background: var(--pink);
        color: #fff;
        padding: .1rem .45rem;
        border-radius: 10px;
        margin-left: .35rem;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .empty-players {
        color: var(--muted);
        font-size: .9rem;
        font-style: italic;
        padding: .5rem 0;
    }

    /* ── GM Controls ── */
    .gm-controls {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-bottom: 1rem;
    }

    .gm-title {
        font-size: .78rem;
        font-weight: 800;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: .75rem;
    }

    .btn-row { display: flex; gap: .75rem; flex-wrap: wrap; margin-top: 1rem; }

    .role-dist {
        background: var(--bg3);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: .8rem 1rem;
        font-size: .82rem;
        color: var(--muted);
        margin-top: .75rem;
        line-height: 1.7;
    }

    .role-dist strong { color: #c084fc; }

    .refresh-note {
        text-align: center;
        color: rgba(155,135,184,0.4);
        font-size: .72rem;
        margin-top: .5rem;
    }
</style>

{{-- Hero --}}
<div class="lobby-hero">
    <div class="lobby-hint">Share this page URL with your players</div>
    <div class="lobby-game-name">{{ $game->name }}</div>
    <span class="status-pill {{ $game->status === 'waiting' ? 'pill-waiting' : ($game->status === 'roles_assigned' ? 'pill-assigned' : 'pill-progress') }}">
        {{ $game->status_label }}
    </span>
</div>

{{-- Join form --}}
@if($game->status === 'waiting' && !$myPlayer)
<div class="lobby-card">
    <h2>👋 Enter your name to join</h2>
    <form method="POST" action="{{ route('games.join', $game) }}">
        @csrf
        <div class="form-group">
            <label for="name">Your name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}"
                   placeholder="e.g. Alice" autocomplete="off" autofocus required>
            @error('name')
                <div class="form-error">{{ $message }}</div>
            @enderror
            @if(session('error'))
                <div class="form-error">{{ session('error') }}</div>
            @endif
        </div>
        <button type="submit" class="btn" style="width:100%; padding:.7rem;">Join Game →</button>
    </form>
</div>
@endif

{{-- Already joined --}}
@if($myPlayer && $game->status === 'waiting')
<div class="lobby-card" style="text-align:center;">
    <span class="status-icon">✅</span>
    <h2 style="justify-content:center;">You're in, {{ $myPlayer->name }}!</h2>
    <p style="color:var(--muted); font-size:.9rem; font-weight:600;">Waiting for the game master to start…</p>
</div>
@endif

{{-- Roles assigned --}}
@if($myPlayer && in_array($game->status, ['roles_assigned', 'in_progress']))
<div class="lobby-card" style="text-align:center;">
    <span class="status-icon">🎭</span>
    <h2 style="justify-content:center; margin-bottom:.75rem;">Roles are ready!</h2>
    <a href="{{ route('games.play', $game) }}" class="btn" style="width:100%; display:block; text-align:center; padding:.7rem;">
        See your role & play →
    </a>
</div>
@endif

{{-- Game already started, no session --}}
@if(!$myPlayer && $game->status !== 'waiting')
<div class="lobby-card" style="text-align:center;">
    <span class="status-icon">🔒</span>
    <p style="color:var(--muted); font-weight:600;">This game has already started.<br>You can't join mid-game.</p>
</div>
@endif

{{-- Player list --}}
<div class="player-list">
    <div class="player-list-header">
        <div class="player-list-title">Players</div>
        <span class="player-count-badge">{{ $game->players->count() }}</span>
    </div>
    @forelse($game->players as $p)
        <div class="player-row">
            <div class="player-avatar">{{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</div>
            <div class="player-name-text">
                {{ $p->name }}
                @if($myPlayer && $myPlayer->id === $p->id)
                    <span class="you-tag">you</span>
                @endif
            </div>
        </div>
    @empty
        <div class="empty-players">No players yet — be the first to join!</div>
    @endforelse
</div>

{{-- GM controls --}}
@if($isGM)
<div class="gm-controls">
    <div class="gm-title">🎮 Game Master Controls</div>

    @if($game->status === 'waiting')
        <p style="color:var(--muted); font-size:.9rem; font-weight:600;">
            Wait for everyone to join, then assign roles.
        </p>
        <div class="role-dist">
            <strong>Role distribution (auto):</strong><br>
            4–6 players → 1 Werewolf · Villagers<br>
            7–8 players → 1 Werewolf · 1 Seer · Villagers<br>
            9+ players  → 2 Werewolves · 1 Seer · 1 Doctor · Villagers
        </div>
        <div class="btn-row">
            <form method="POST" action="{{ route('games.assign-roles', $game) }}">
                @csrf
                <button type="submit" class="btn">🎲 Assign Roles & Start →</button>
            </form>
        </div>
    @endif

    @if($game->status === 'roles_assigned')
        <p style="color:var(--green); font-size:.9rem; font-weight:700;">✅ Roles assigned — players can now see their role.</p>
        <div class="btn-row">
            <form method="POST" action="{{ route('games.assign-roles', $game) }}">
                @csrf
                <button type="submit" class="btn-outline">🔄 Re-assign Roles</button>
            </form>
            <form method="POST" action="{{ route('games.start', $game) }}">
                @csrf
                <button type="submit" class="btn-green">▶ Start Game</button>
            </form>
        </div>
    @endif

    @if($game->status === 'in_progress')
        <a href="{{ route('games.play', $game) }}" class="btn-green">🎮 Go to Game Board →</a>
    @endif
</div>
@endif

@if($game->status === 'waiting')
    <meta http-equiv="refresh" content="5">
    <p class="refresh-note">Refreshes every 5 seconds</p>
@endif
@endsection
