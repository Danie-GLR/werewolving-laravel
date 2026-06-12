{{-- resources/views/games/lobby.blade.php --}}
@extends('layouts.app')

@section('title', $game->name . ' — Lobby')

@section('content')

{{-- Auto-refresh every 5 minutes to pick up new players --}}
<meta http-equiv="refresh" content="300">

<style>
    .lobby-title { font-size: clamp(1.8rem, 5vw, 2.6rem); font-weight: 900; margin-bottom: .3rem; }
    .status-badge {
        display: inline-flex; align-items: center; gap: .4rem;
        background: var(--bg3); border: 1px solid var(--border);
        border-radius: 99px; padding: .3rem .9rem;
        font-size: .78rem; font-weight: 800; color: var(--muted);
        text-transform: uppercase; letter-spacing: .07em; margin-bottom: 1.5rem;
    }
    .join-card {
        background: var(--bg2); border: 1px solid var(--border);
        border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.5rem;
    }
    .join-card h2 { font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem; }
    .field-label { font-size: .75rem; font-weight: 800; color: var(--muted);
        text-transform: uppercase; letter-spacing: .07em; margin-bottom: .4rem; }
    .players-card {
        background: var(--bg2); border: 1px solid var(--border);
        border-radius: var(--radius); padding: 1.2rem 1.5rem;
    }
    .players-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 1rem;
    }
    .players-header h2 { font-size: 1rem; font-weight: 800; }
    .player-count {
        background: var(--pink); color: #fff;
        border-radius: 99px; font-size: .75rem; font-weight: 800;
        padding: .15rem .55rem;
    }
    .player-row {
        display: flex; align-items: center; gap: .75rem;
        padding: .55rem 0; border-bottom: 1px solid var(--border);
    }
    .player-row:last-child { border-bottom: none; }
    .player-avatar {
        width: 36px; height: 36px; border-radius: 99px;
        background: rgba(255,61,127,0.15); border: 1.5px solid rgba(255,61,127,0.3);
        display: flex; align-items: center; justify-content: center;
        font-weight: 900; font-size: .9rem; color: var(--pink); flex-shrink: 0;
    }
    .player-name { font-weight: 700; font-size: .95rem; }
    .you-badge {
        font-size: .7rem; font-weight: 800; color: var(--pink);
        background: rgba(255,61,127,0.12); border-radius: 99px;
        padding: .1rem .45rem; margin-left: .4rem;
    }
    .gm-controls { margin-top: 1.5rem; }
    .gm-controls h3 { font-size: .8rem; font-weight: 800; color: var(--muted);
        text-transform: uppercase; letter-spacing: .07em; margin-bottom: .75rem; }
    .share-url {
        background: var(--bg3); border: 1px solid var(--border);
        border-radius: var(--radius); padding: .6rem .9rem;
        font-size: .82rem; color: var(--muted); font-family: monospace;
        word-break: break-all; margin-bottom: 1rem;
    }
    .already-joined {
        background: rgba(255,61,127,0.08); border: 1px solid rgba(255,61,127,0.2);
        border-radius: var(--radius); padding: 1rem 1.2rem;
        margin-bottom: 1.5rem; font-weight: 700; font-size: .95rem;
    }
    .refresh-note { text-align: center; color: var(--muted); font-size: .78rem; margin-top: 1.5rem; }
</style>

<div style="text-align:center; margin-bottom:.5rem;">
    <p style="color:var(--muted); font-size:.85rem; margin-bottom:.5rem;">Share this page URL with your players</p>
    <div class="lobby-title">{{ $game->name }}</div>
    <div class="status-badge">{{ $game->status_label }}</div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error" style="margin-bottom:1rem;">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1rem;">{{ $errors->first() }}</div>
@endif

{{-- ── Join form: only shown when the player hasn't joined yet ── --}}
@if($game->status === 'waiting' && !$myPlayer && !$isGM)
    <div class="join-card">
        <h2>👋 Enter your name to join</h2>
        <form method="POST" action="{{ route('games.join', $game) }}">
            @csrf
            <div class="field-label">Your name</div>
            <input
                class="input"
                type="text"
                name="name"
                placeholder="e.g. Alice"
                value="{{ old('name') }}"
                maxlength="40"
                autocomplete="off"
                autofocus
                style="margin-bottom:.75rem;"
            >
            <button type="submit" class="btn" style="width:100%;">Join Game →</button>
        </form>
    </div>
@elseif($myPlayer)
    {{-- Already joined — show their identity instead of the form --}}
    <div class="already-joined">
        ✅ You're in as <strong>{{ $myPlayer->name }}</strong>. Wait for the game master to start!
    </div>
@elseif($isGM && $game->status === 'waiting')
    {{-- GM sees nothing here — their controls are below --}}
@endif

{{-- ── Players list ── --}}
<div class="players-card">
    <div class="players-header">
        <h2>Players</h2>
        <span class="player-count">{{ $game->players->count() }}</span>
    </div>

    @forelse($game->players as $player)
        <div class="player-row">
            <div class="player-avatar">{{ strtoupper(substr($player->name, 0, 1)) }}</div>
            <div class="player-name">
                {{ $player->name }}
                @if($myPlayer && $myPlayer->id === $player->id)
                    <span class="you-badge">you</span>
                @endif
            </div>
        </div>
    @empty
        <p style="color:var(--muted); font-size:.88rem;">No players yet — share the link!</p>
    @endforelse
</div>

{{-- ── Game Master controls ── --}}
@if($isGM)
    <div class="gm-controls">
        <h3>🎭 Game Master Controls</h3>

        <div class="share-url">{{ url()->current() }}</div>

        @if($game->status === 'waiting')
            <form method="POST" action="{{ route('games.assign-roles', $game) }}" style="margin-bottom:.75rem;">
                @csrf
                <button type="submit" class="btn" style="width:100%;"
                    {{ $game->players->count() < 4 ? 'disabled' : '' }}>
                    🎲 Assign Roles
                    @if($game->players->count() < 4)
                        (need {{ 4 - $game->players->count() }} more)
                    @endif
                </button>
            </form>
        @endif

        @if(in_array($game->status, ['roles_assigned']))
            <form method="POST" action="{{ route('games.start', $game) }}">
                @csrf
                <button type="submit" class="btn" style="width:100%;">🌙 Start Game</button>
            </form>
        @endif

        @if($game->status === 'in_progress')
            <a href="{{ route('games.play', $game) }}" class="btn" style="display:block; text-align:center; width:100%;">
                ▶ Go to Game Board
            </a>
        @endif
    </div>
@endif

{{-- My role link (once assigned) --}}
@if($myPlayer && in_array($game->status, ['roles_assigned', 'in_progress', 'finished']))
    <div style="margin-top:1.2rem; text-align:center;">
        <a href="{{ route('games.my-role', $game) }}" class="btn" style="display:inline-block;">
            🔍 See My Role
        </a>
        @if($game->status === 'in_progress')
            <a href="{{ route('games.play', $game) }}" class="btn"
               style="display:inline-block; margin-left:.5rem; background:var(--bg3); color:var(--text);">
                ▶ Game Board
            </a>
        @endif
    </div>
@endif

<p class="refresh-note">Refreshes every 5 minutes</p>

@endsection
