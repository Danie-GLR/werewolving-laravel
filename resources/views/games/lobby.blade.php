@extends('layouts.app')
@section('title', 'Lobby — ' . $game->name)
@section('content')
<style>
    .lobby-title { font-family:'Fredoka One',cursive; font-size:2.4rem; color:var(--pink); text-align:center; margin-bottom:.25rem; text-shadow:0 3px 10px rgba(232,54,106,.35); }
    .lobby-sub   { text-align:center; color:var(--muted); font-weight:700; margin-bottom:1.2rem; }

    .join-card   { background:var(--surface); border-radius:var(--radius); padding:1.5rem; max-width:420px; margin:0 auto 1.2rem; box-shadow:var(--shadow); }
    .form-label  { display:block; font-weight:800; font-size:.82rem; margin-bottom:.4rem; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
    .form-error  { color:#ff6b95; font-size:.82rem; font-weight:700; margin-top:.3rem; }

    /* Player list */
    .player-list { background:var(--surface); border-radius:var(--radius); padding:1.2rem; margin-bottom:1rem; box-shadow:var(--shadow); }
    .player-row  { display:flex; align-items:center; gap:.85rem; padding:.55rem 0; border-bottom:1px solid var(--surface2); }
    .player-row:last-child { border-bottom:none; }
    .p-avatar    { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-family:'Fredoka One',cursive; font-size:1.1rem; color:#fff; flex-shrink:0; }
    .p-name      { font-weight:800; font-size:.95rem; }
    .you-tag     { background:var(--pink); color:#fff; font-size:.68rem; font-weight:800; padding:.1rem .5rem; border-radius:20px; margin-left:.4rem; text-transform:uppercase; }
    .empty-state { color:var(--muted); font-style:italic; font-size:.9rem; padding:.5rem 0; }

    /* GM panel */
    .gm-panel    { background:var(--surface); border-radius:var(--radius); padding:1.2rem 1.4rem; border:2px solid var(--pink); box-shadow:var(--shadow); }
    .gm-label    { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:var(--pink); margin-bottom:.6rem; }
    .btn-row     { display:flex; gap:.75rem; flex-wrap:wrap; margin-top:.75rem; }

    .role-dist   { background:var(--bg); border-radius:var(--radius-sm); padding:.75rem 1rem; font-size:.82rem; color:var(--muted); margin-top:.75rem; font-weight:600; }
    .role-dist strong { color:#c084fc; }

    /* Status pill */
    .status-pill { display:inline-flex; align-items:center; justify-content:center; padding:.3rem 1rem; border-radius:20px; font-size:.8rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }

    /* Avatar colours cycle */
    .av0{background:#e8366a;} .av1{background:#6abf4b;} .av2{background:#f5c842;color:#333;}
    .av3{background:#8e44ad;} .av4{background:#2980b9;} .av5{background:#e05a2b;}
    .av6{background:#16a085;} .av7{background:#c0392b;}
</style>

<div class="lobby-title">{{ $game->name }}</div>
<div class="lobby-sub">
    @if($game->status==='waiting')
        <span class="status-pill badge-waiting">⏳ Waiting for players</span>
    @elseif($game->status==='roles_assigned')
        <span class="status-pill badge-assigned">🎭 Roles assigned</span>
    @elseif($game->status==='in_progress')
        <span class="status-pill badge-progress">🐺 In progress</span>
    @else
        <span class="status-pill badge-finished">✅ Finished</span>
    @endif
</div>

{{-- Join form --}}
@if($game->status === 'waiting' && !$myPlayer)
    <div class="join-card">
        <h2 style="margin-bottom:1rem;">👋 Join Game</h2>
        <form method="POST" action="{{ route('games.join', $game) }}">
            @csrf
            <div style="margin-bottom:1rem;">
                <label class="form-label" for="name">Your name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                       placeholder="e.g. Alice" autocomplete="off" autofocus required>
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
                @if(session('error'))<div class="form-error">{{ session('error') }}</div>@endif
            </div>
            <button type="submit" class="btn" style="width:100%;">Join →</button>
        </form>
    </div>
@endif

{{-- Waiting confirmation --}}
@if($myPlayer && $game->status === 'waiting')
    <div class="join-card" style="text-align:center;">
        <div style="font-size:3rem;margin-bottom:.5rem;">✅</div>
        <h2 style="margin-bottom:.4rem;">You're in!</h2>
        <p class="text-muted" style="font-weight:600;">Waiting for the game master to start…</p>
    </div>
@endif

{{-- Roles ready --}}
@if($myPlayer && in_array($game->status, ['roles_assigned','in_progress']))
    <div class="join-card" style="text-align:center;">
        <div style="font-size:3rem;margin-bottom:.5rem;">🎭</div>
        <h2 style="margin-bottom:.75rem;">Roles are ready!</h2>
        <a href="{{ route('games.play', $game) }}" class="btn" style="width:100%;display:block;text-align:center;">
            See your role &amp; play →
        </a>
    </div>
@endif

{{-- Locked out --}}
@if(!$myPlayer && $game->status !== 'waiting')
    <div class="join-card" style="text-align:center;">
        <div style="font-size:3rem;margin-bottom:.5rem;">🔒</div>
        <p class="text-muted" style="font-weight:700;">This game has already started.</p>
    </div>
@endif

{{-- Player list --}}
<div class="player-list">
    <h2 style="margin-bottom:.9rem;">🧑‍🤝‍🧑 Players ({{ $game->players->count() }})</h2>
    @forelse($game->players as $i => $p)
        <div class="player-row">
            <div class="p-avatar av{{ $i % 8 }}">{{ mb_strtoupper(mb_substr($p->name,0,1)) }}</div>
            <div class="p-name">
                {{ $p->name }}
                @if($myPlayer && $myPlayer->id === $p->id)
                    <span class="you-tag">you</span>
                @endif
            </div>
        </div>
    @empty
        <div class="empty-state">No players yet — be the first!</div>
    @endforelse
</div>

{{-- GM controls --}}
@if($isGM)
    <div class="gm-panel">
        <div class="gm-label">🎮 Game Master Controls</div>

        @if($game->status === 'waiting')
            <p class="text-muted" style="font-size:.88rem;font-weight:600;">Wait for everyone to join, then assign roles.</p>
            <div class="role-dist">
                <strong>Role distribution (auto):</strong><br>
                4–6 players → 1 Werewolf · Villagers<br>
                7–8 players → 1 Werewolf · 1 Seer · Villagers<br>
                9+ players  → 2 Werewolves · 1 Seer · 1 Doctor · Villagers
            </div>
            <div class="btn-row">
                <form method="POST" action="{{ route('games.assign-roles', $game) }}">
                    @csrf
                    <button type="submit" class="btn">🎲 Assign Roles</button>
                </form>
            </div>
        @endif

        @if($game->status === 'roles_assigned')
            <p style="color:#6ee7a0;font-size:.88rem;font-weight:700;">✅ Roles assigned — players can now see their role.</p>
            <div class="btn-row">
                <form method="POST" action="{{ route('games.assign-roles', $game) }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">🔄 Re-assign</button>
                </form>
                <form method="POST" action="{{ route('games.start', $game) }}">
                    @csrf
                    <button type="submit" class="btn btn-green-fill">▶ Start Game</button>
                </form>
            </div>
        @endif

        @if($game->status === 'in_progress')
            <a href="{{ route('games.play', $game) }}" class="btn btn-green-fill">🎮 Go to Game Board →</a>
        @endif
    </div>
@endif

@if($game->status === 'waiting')
    <meta http-equiv="refresh" content="5">
    <p style="text-align:center;color:#555;font-size:.75rem;margin-top:1rem;font-weight:600;">Refreshes every 5 seconds</p>
@endif
@endsection
