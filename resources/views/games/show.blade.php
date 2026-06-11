@extends('layouts.app')

@section('title', $game->name)

@section('content')
<style>
    .stats { display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
    .stat  { background:#16213e; border-radius:8px; padding:.8rem 1.2rem; flex:1; min-width:90px; text-align:center; }
    .stat .value { font-size:1.8rem; font-weight:bold; }
    .stat .label { font-size:.75rem; color:#9ca3af; margin-top:.2rem; }
    .flex { display:flex; gap:.75rem; align-items:center; flex-wrap:wrap; }
    .mt-1 { margin-top:.4rem; }
    .mt-3 { margin-top:1.5rem; }
    .text-muted { color:#9ca3af; font-size:.85rem; }
    .badge { padding:.2rem .7rem; border-radius:12px; font-size:.75rem; font-weight:bold; }
    .badge-waiting  { background:#1f2937; color:#9ca3af; }
    .badge-assigned { background:#1a1a4d; color:#a5b4fc; }
    .badge-progress { background:#2d1a00; color:#fcd34d; }
    .badge-finished { background:#0d2d18; color:#86efac; }

    .player-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:.75rem; margin:1rem 0; }
    .player-card { background:#0f172a; border:1px solid #1e3a5f; border-radius:8px; padding:1rem .75rem; text-align:center; }
    .player-card.dead { opacity:.4; }
    .player-card .avatar { font-size:2rem; margin-bottom:.4rem; }
    .player-card .player-name { font-weight:bold; font-size:.9rem; }

    .role-chip { font-size:.72rem; padding:.15rem .5rem; border-radius:10px; display:inline-block; margin-top:.3rem; }
    .role-chip.role-Werewolf { background:#4d0b0b; color:#fca5a5; }
    .role-chip.role-Villager { background:#0d2d18; color:#86efac; }
    .role-chip.role-Seer     { background:#2d1a4d; color:#d8b4fe; }
    .role-chip.role-Doctor   { background:#0d1a3d; color:#93c5fd; }
    .role-chip.role-unknown  { background:#1f2937; color:#9ca3af; }

    .btn-outline { background:transparent; border:1px solid #e94560; color:#e94560;
                   padding:.5rem 1.2rem; border-radius:4px; cursor:pointer;
                   text-decoration:none; font-size:.95rem; }
    .btn-outline:hover { background:#e94560; color:#fff; }
    .btn-green { background:#16a34a; }
    .btn-green:hover { background:#15803d; }

    table { width:100%; border-collapse:collapse; }
    th,td { padding:.6rem .8rem; text-align:left; }
    th { border-bottom:1px solid #2a2a40; color:#9ca3af; font-size:.8rem; text-transform:uppercase; }
    tr:not(:last-child) td { border-bottom:1px solid #1e293b; }
</style>

<a href="{{ route('games.index') }}" class="text-muted" style="display:inline-block; margin-bottom:1rem;">← All Games</a>

<div class="flex" style="margin-bottom:1.5rem; align-items:flex-start;">
    <div style="flex:1;">
        <h1>{{ $game->name }}</h1>
        <div class="flex mt-1">
            @php
                $badgeClass = match($game->status) {
                    'waiting'        => 'badge-waiting',
                    'roles_assigned' => 'badge-assigned',
                    'in_progress'    => 'badge-progress',
                    'finished'       => 'badge-finished',
                    default          => 'badge-waiting',
                };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $game->status_label }}</span>
            <span class="text-muted">{{ $game->players->count() }} players · Created {{ $game->created_at->diffForHumans() }}</span>
        </div>
    </div>

    <div class="flex">
        @if($game->status === 'waiting')
            <form method="POST" action="{{ route('games.assign-roles', $game) }}">
                @csrf
                <button type="submit" class="btn">🎲 Assign Roles</button>
            </form>
        @endif

        @if($game->status === 'roles_assigned')
            <form method="POST" action="{{ route('games.assign-roles', $game) }}">
                @csrf
                <button type="submit" class="btn btn-outline">🔄 Re-assign Roles</button>
            </form>
            <form method="POST" action="{{ route('games.start', $game) }}">
                @csrf
                <button type="submit" class="btn btn-green">▶ Start Game</button>
            </form>
        @endif

        @if($game->status === 'in_progress' || $game->status === 'finished')
            <a href="{{ route('games.play', $game) }}" class="btn btn-green">🎮 Game Board</a>
        @endif
    </div>
</div>

{{-- Stats --}}
@php
    $alive    = $game->players->where('is_alive', true);
    $wolves   = $game->players->where('role', 'Werewolf')->count();
@endphp

<div class="stats">
    <div class="stat">
        <div class="value">{{ $game->players->count() }}</div>
        <div class="label">Total Players</div>
    </div>
    @if($game->status !== 'waiting')
        <div class="stat">
            <div class="value" style="color:#fca5a5;">{{ $wolves }}</div>
            <div class="label">Werewolves 🐺</div>
        </div>
        <div class="stat">
            <div class="value" style="color:#86efac;">{{ $game->players->whereNotIn('role', ['Werewolf'])->count() }}</div>
            <div class="label">Villagers & Specials</div>
        </div>
        <div class="stat">
            <div class="value" style="color:#fde68a;">{{ $alive->count() }}</div>
            <div class="label">Still Alive</div>
        </div>
    @endif
</div>

{{-- Player grid --}}
<h2>Players</h2>

@if($game->status === 'waiting')
    <p style="margin-bottom:1rem; color:#9ca3af;">Roles haven't been assigned yet. Click <strong>Assign Roles</strong> above.</p>
@endif

<div class="player-grid">
    @foreach($game->players as $player)
        <div class="player-card {{ !$player->is_alive ? 'dead' : '' }}">
            <div class="avatar">
                {{ $game->status !== 'waiting' ? $player->role_icon : '❓' }}
            </div>
            <div class="player-name">{{ $player->name }}</div>
            @if($game->status !== 'waiting' && $player->role)
                <span class="role-chip role-{{ $player->role }}">{{ $player->role }}</span>
            @else
                <span class="role-chip role-unknown">No role yet</span>
            @endif
            @if(!$player->is_alive)
                <div class="text-muted" style="font-size:.75rem; margin-top:.3rem;">☠️ Eliminated</div>
            @endif
        </div>
    @endforeach
</div>

{{-- Secret links table (shown once roles are assigned) --}}
@if($game->status !== 'waiting')
    <div class="card mt-3">
        <h2 style="margin-top:0;">🔗 Secret Role Links</h2>
        <p style="color:#9ca3af; font-size:.85rem; margin-bottom:1rem;">
            Share each player's link privately — they'll see only their own role.
            Since this server is public, anyone with the link can view it.
        </p>
        <table>
            <thead>
                <tr>
                    <th>Player</th>
                    <th>Secret Link</th>
                </tr>
            </thead>
            <tbody>
                @foreach($game->players as $player)
                    <tr>
                        <td><strong>{{ $player->name }}</strong></td>
                        <td>
                            @if($player->token)
                                <span style="font-family:monospace; font-size:.78rem; color:#94a3b8; word-break:break-all;">
                                    {{ $player->roleRevealUrl() }}
                                </span>
                                <button onclick="copyText(this, '{{ $player->roleRevealUrl() }}')"
                                        style="background:#1e293b; border:1px solid #475569; color:#cbd5e1;
                                               padding:.15rem .5rem; border-radius:3px; cursor:pointer; font-size:.72rem; margin-left:.4rem;">
                                    Copy
                                </button>
                            @else
                                <span style="color:#6b7280;">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Role legend --}}
@if($game->status !== 'waiting')
    <div class="card mt-3">
        <h2 style="margin-top:0;">Role Guide</h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:.75rem; margin-top:.5rem;">
            <div>
                <span class="role-chip role-Werewolf">🐺 Werewolf</span>
                <p style="font-size:.8rem; margin-top:.35rem;">Wakes at night to eliminate a player. Wins when wolves ≥ village.</p>
            </div>
            <div>
                <span class="role-chip role-Villager">👨‍🌾 Villager</span>
                <p style="font-size:.8rem; margin-top:.35rem;">Votes during the day to eliminate suspected werewolves.</p>
            </div>
            @if($game->players->where('role','Seer')->count())
            <div>
                <span class="role-chip role-Seer">🔮 Seer</span>
                <p style="font-size:.8rem; margin-top:.35rem;">Peeks at one player's role each night. Must guide the village wisely.</p>
            </div>
            @endif
            @if($game->players->where('role','Doctor')->count())
            <div>
                <span class="role-chip role-Doctor">💊 Doctor</span>
                <p style="font-size:.8rem; margin-top:.35rem;">Protects one player from elimination each night.</p>
            </div>
            @endif
        </div>
    </div>
@endif

<script>
function copyText(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = '✓';
        setTimeout(() => btn.textContent = 'Copy', 1500);
    });
}
</script>
@endsection
