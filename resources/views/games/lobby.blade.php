@extends('layouts.app')

@section('title', 'Join — ' . $game->name)

@section('content')
<style>
    .lobby-hero { text-align:center; padding:2rem 1rem 1.5rem; }
    .game-code  { font-size:2.5rem; font-weight:900; letter-spacing:.15em; color:#e94560; margin:.5rem 0; }
    .status-pill{display:inline-block;padding:.25rem .9rem;border-radius:20px;font-size:.8rem;font-weight:bold;}
    .pill-waiting  {background:#1f2937;color:#9ca3af;border:1px solid #374151;}
    .pill-assigned {background:#1a1a4d;color:#a5b4fc;border:1px solid #4f46e5;}
    .pill-progress {background:#2d1a00;color:#fcd34d;border:1px solid #d97706;}

    .join-card  { background:#16213e;border-radius:10px;padding:1.5rem;max-width:420px;margin:0 auto 1.5rem; }
    .join-card h2{ margin-top:0; }
    .form-group { margin-bottom:1rem; }
    .form-group label{ display:block;margin-bottom:.4rem;font-size:.85rem;color:#9ca3af; }
    .form-group input{ width:100%;box-sizing:border-box;background:#0f172a;border:1px solid #334155;
                        color:#eee;padding:.6rem .8rem;border-radius:6px;font-size:1rem; }
    .form-group input:focus{ outline:none;border-color:#e94560; }
    .form-error { color:#fca5a5;font-size:.8rem;margin-top:.3rem; }

    .player-list{ background:#16213e;border-radius:10px;padding:1.2rem 1.5rem;margin-bottom:1rem; }
    .player-list h2{ margin-top:0;margin-bottom:.75rem; }
    .player-row { display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid #1e293b; }
    .player-row:last-child{border-bottom:none;}
    .player-avatar{ width:36px;height:36px;background:#0f172a;border-radius:50%;
                     display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
    .player-name-text{font-weight:bold;}
    .you-tag{font-size:.7rem;background:#e94560;color:#fff;padding:.1rem .4rem;border-radius:8px;margin-left:.4rem;}
    .empty-state{color:#6b7280;font-style:italic;font-size:.9rem;padding:.5rem 0;}

    .gm-controls{background:#0f172a;border:1px solid #334155;border-radius:10px;padding:1.2rem 1.5rem;margin-bottom:1rem;}
    .gm-controls h2{margin-top:0;font-size:1rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;}
    .btn-row{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.75rem;}
    .btn-outline{background:transparent;border:1px solid #e94560;color:#e94560;padding:.5rem 1.2rem;
                  border-radius:4px;cursor:pointer;text-decoration:none;font-size:.95rem;}
    .btn-outline:hover{background:#e94560;color:#fff;}
    .btn-green{background:#16a34a;color:#fff;padding:.5rem 1.2rem;border:none;border-radius:4px;cursor:pointer;font-size:.95rem;}
    .btn-green:hover{background:#15803d;}
    .hint{color:#6b7280;font-size:.8rem;margin-top:.5rem;}

    .role-dist{background:#0f0f1a;border:1px solid #2a2a40;border-radius:6px;
               padding:.7rem 1rem;font-size:.8rem;color:#9ca3af;margin-top:.75rem;}
    .role-dist strong{color:#c084fc;}
</style>

<div class="lobby-hero">
    <div style="color:#9ca3af;font-size:.9rem;">Share this page URL with everyone</div>
    <div class="game-code">{{ $game->name }}</div>
    <span class="status-pill {{ $game->status === 'waiting' ? 'pill-waiting' : ($game->status === 'roles_assigned' ? 'pill-assigned' : 'pill-progress') }}">
        {{ $game->status_label }}
    </span>
</div>

{{-- ── Join form (only shown when game is still open and device hasn't joined) ── --}}
@if($game->status === 'waiting' && !$myPlayer)
    <div class="join-card">
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
            <button type="submit" class="btn" style="width:100%;">Join Game →</button>
        </form>
    </div>
@endif

{{-- ── Already joined ── --}}
@if($myPlayer && $game->status === 'waiting')
    <div class="join-card" style="text-align:center;">
        <div style="font-size:2.5rem;">✅</div>
        <h2 style="margin:.5rem 0;">You're in, {{ $myPlayer->name }}!</h2>
        <p style="color:#9ca3af;">Waiting for the game master to start…</p>
    </div>
@endif

{{-- ── Roles have been assigned — go see yours ── --}}
@if($myPlayer && in_array($game->status, ['roles_assigned', 'in_progress']))
    <div class="join-card" style="text-align:center;">
        <div style="font-size:2.5rem;">🎭</div>
        <h2 style="margin:.5rem 0;">Roles are ready!</h2>
        <a href="{{ route('games.play', $game) }}" class="btn" style="width:100%;display:block;box-sizing:border-box;text-align:center;">
            See your role & play →
        </a>
    </div>
@endif

{{-- ── Game already started, device has no session ── --}}
@if(!$myPlayer && $game->status !== 'waiting')
    <div class="join-card" style="text-align:center;">
        <div style="font-size:2.5rem;">🔒</div>
        <p style="color:#9ca3af;">This game has already started.<br>You can't join mid-game.</p>
    </div>
@endif

{{-- ── Player list ── --}}
<div class="player-list">
    <h2>🧑‍🤝‍🧑 Players ({{ $game->players->count() }})</h2>
    @forelse($game->players as $p)
        <div class="player-row">
            <div class="player-avatar">{{ mb_substr($p->name, 0, 1) }}</div>
            <div class="player-name-text">
                {{ $p->name }}
                @if($myPlayer && $myPlayer->id === $p->id)
                    <span class="you-tag">you</span>
                @endif
            </div>
        </div>
    @empty
        <div class="empty-state">No players yet — be the first to join!</div>
    @endforelse
</div>

{{-- ── Game master controls ── --}}
@if($isGM)
    <div class="gm-controls">
        <h2>🎮 Game Master Controls</h2>

        @if($game->status === 'waiting')
            <p style="color:#9ca3af;font-size:.9rem;margin:.25rem 0 0;">
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
            <p style="color:#86efac;font-size:.9rem;">✅ Roles assigned — players can now see their role.</p>
            <div class="btn-row">
                <form method="POST" action="{{ route('games.assign-roles', $game) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">🔄 Re-assign Roles</button>
                </form>
                <form method="POST" action="{{ route('games.start', $game) }}">
                    @csrf
                    <button type="submit" class="btn btn-green">▶ Start Game</button>
                </form>
            </div>
        @endif

        @if($game->status === 'in_progress')
            <a href="{{ route('games.play', $game) }}" class="btn btn-green">🎮 Go to Game Board →</a>
        @endif
    </div>
@endif

{{-- Auto-refresh while waiting --}}
@if($game->status === 'waiting')
    <meta http-equiv="refresh" content="5">
    <p style="text-align:center;color:#4b5563;font-size:.75rem;">Page refreshes every 5 seconds</p>
@endif

@endsection
