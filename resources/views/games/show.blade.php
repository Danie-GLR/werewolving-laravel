@extends('layouts.app')

@section('title', $game->name)

@section('content')
<style>
    .stats { display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
    .stat  { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); padding:.8rem 1.2rem; flex:1; min-width:90px; text-align:center; }
    .stat .value { font-size:1.8rem; font-weight:900; }
    .stat .label { font-size:.75rem; color:var(--muted); margin-top:.2rem; font-weight:700; text-transform:uppercase; }
    .flex { display:flex; gap:.75rem; align-items:center; flex-wrap:wrap; }
    .mt-1 { margin-top:.4rem; }
    .mt-3 { margin-top:1.5rem; }

    .player-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:.75rem; margin:1rem 0; }
    .player-card { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); padding:1rem .75rem; text-align:center; }
    .player-card.dead { opacity:.4; }
    .player-card .avatar { font-size:2rem; margin-bottom:.4rem; }
    .player-card .player-name { font-weight:800; font-size:.9rem; }

    .role-chip { font-size:.72rem; padding:.15rem .5rem; border-radius:10px; display:inline-block; margin-top:.3rem; font-weight:800; }
    .role-chip.role-Werewolf { background:rgba(233,69,96,0.2);   color:#fca5a5; }
    .role-chip.role-Villager { background:rgba(45,206,137,0.15); color:#86efac; }
    .role-chip.role-Seer     { background:rgba(168,85,247,0.15); color:#d8b4fe; }
    .role-chip.role-Doctor   { background:rgba(59,130,246,0.15); color:#93c5fd; }
    .role-chip.role-unknown  { background:rgba(155,135,184,0.1); color:var(--muted); }

    table { width:100%; border-collapse:collapse; }
    th,td { padding:.6rem .8rem; text-align:left; }
    th { border-bottom:1px solid var(--border); color:var(--muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; }
    tr:not(:last-child) td { border-bottom:1px solid var(--border); }

    .page-title { font-size:1.8rem; font-weight:900; letter-spacing:-.5px; }
    .back-link { color:var(--muted); text-decoration:none; font-weight:700; font-size:.9rem; display:inline-block; margin-bottom:1rem; transition:color .2s; }
    .back-link:hover { color:var(--text); }

    .section-title { font-size:.8rem; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:.75rem; margin-top:1.5rem; }

    .btn-edit {
        background: transparent;
        border: 1.5px solid var(--border);
        color: var(--muted);
        font-family: 'Nunito', sans-serif;
        font-weight: 800;
        font-size: .85rem;
        padding: .4rem 1rem;
        border-radius: 30px;
        cursor: pointer;
        text-decoration: none;
        transition: all .2s;
    }
    .btn-edit:hover { border-color: var(--pink); color: var(--pink); }
</style>

<a href="{{ route('games.index') }}" class="back-link">← All Games</a>

<div class="flex" style="margin-bottom:1.5rem; align-items:flex-start;">
    <div style="flex:1;">
        <div class="page-title">{{ $game->name }}</div>
        <div class="flex mt-1">
            @php
                $badgeClass = match($game->status) {
                    'waiting'     => 'badge-waiting',
                    'in_progress' => 'badge-progress',
                    'finished'    => 'badge-finished',
                    default       => 'badge-waiting',
                };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $game->status_label }}</span>
            <span class="text-muted" style="font-size:.82rem;">{{ $game->players->count() }} players · Created {{ $game->created_at->diffForHumans() }}</span>
        </div>
    </div>

    <div class="flex">
        @if($isGM)
            <a href="{{ route('games.edit', $game) }}" class="btn-edit">✏️ Edit</a>

            @if($game->status === 'waiting')
                <form method="POST" action="{{ route('games.assign-roles', $game) }}">
                    @csrf
                    <button type="submit" class="btn">🎲 Assign Roles</button>
                </form>
            @endif

{{-- roles_assigned status removed; game starts immediately on role assignment --}}
        @endif

        @if(in_array($game->status, ['in_progress', 'finished']))
            <a href="{{ route('games.play', $game) }}" class="btn-green">🎮 Game Board</a>
        @endif
    </div>
</div>

{{-- Stats --}}
@php
    $alive  = $game->players->where('is_alive', true);
    $wolves = $game->players->where('role', 'Werewolf')->count();
@endphp

<div class="stats">
    <div class="stat">
        <div class="value">{{ $game->players->count() }}</div>
        <div class="label">Total Players</div>
    </div>
    @if($game->status !== 'waiting')
        @if($isGM)
            <div class="stat">
                <div class="value" style="color:#fca5a5;">{{ $wolves }}</div>
                <div class="label">Werewolves</div>
            </div>
            <div class="stat">
                <div class="value" style="color:#86efac;">{{ $game->players->whereNotIn('role',['Werewolf'])->count() }}</div>
                <div class="label">Village</div>
            </div>
        @endif
        <div class="stat">
            <div class="value" style="color:var(--yellow);">{{ $alive->count() }}</div>
            <div class="label">Alive</div>
        </div>
    @endif
</div>

{{-- Players --}}
<div class="section-title">Players</div>

@if($game->status === 'waiting')
    <p style="margin-bottom:1rem; color:var(--muted); font-size:.9rem; font-weight:600;">Roles haven't been assigned yet.</p>
@endif

<div class="player-grid">
    @foreach($game->players as $player)
        @php
            $revealRole = $isGM || $game->status === 'finished' || !$player->is_alive;
        @endphp
        <div class="player-card {{ !$player->is_alive ? 'dead' : '' }}">
            <div class="avatar">{{ ($game->status !== 'waiting' && $revealRole) ? $player->role_icon : '👤' }}</div>
            <div class="player-name">{{ $player->name }}</div>
            @if($game->status !== 'waiting' && $player->role && $revealRole)
                <span class="role-chip role-{{ $player->role }}">{{ $player->role }}</span>
            @elseif($game->status === 'waiting')
                <span class="role-chip role-unknown">No role</span>
            @endif
            @if(!$player->is_alive)
                <div style="font-size:.7rem; color:var(--red); margin-top:.3rem; font-weight:800;">☠️ Eliminated</div>
            @endif
        </div>
    @endforeach
</div>

{{-- Secret links — GM only, these are private reveal URLs --}}
@if($game->status !== 'waiting' && $isGM)
    <div class="card mt-3">
        <h2 style="margin-top:0; margin-bottom:.5rem; font-size:1.1rem; font-weight:900;">🔗 Secret Role Links</h2>
        <p style="color:var(--muted); font-size:.85rem; margin-bottom:1rem; font-weight:600;">
            Share each player's link privately — they'll see only their own role.
        </p>
        <table>
            <thead>
                <tr><th>Player</th><th>Secret Link</th></tr>
            </thead>
            <tbody>
                @foreach($game->players as $player)
                    <tr>
                        <td><strong>{{ $player->name }}</strong></td>
                        <td>
                            @if($player->token)
                                <span style="font-family:monospace; font-size:.76rem; color:var(--muted); word-break:break-all;">
                                    {{ $player->roleRevealUrl() }}
                                </span>
                                <button onclick="copyText(this, '{{ $player->roleRevealUrl() }}')"
                                        style="background:var(--bg3); border:1px solid var(--border); color:var(--muted);
                                               padding:.15rem .5rem; border-radius:4px; cursor:pointer; font-size:.7rem;
                                               margin-left:.4rem; font-family:'Nunito',sans-serif; font-weight:700;">
                                    Copy
                                </button>
                            @else
                                <span style="color:var(--muted);">—</span>
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
        <h2 style="margin-top:0; margin-bottom:.75rem; font-size:1.1rem; font-weight:900;">Role Guide</h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:.75rem;">
            <div>
                <span class="role-chip role-Werewolf">🐺 Werewolf</span>
                <p style="font-size:.8rem; margin-top:.35rem; color:var(--muted);">Wakes at night to eliminate a player.</p>
            </div>
            <div>
                <span class="role-chip role-Villager">👨‍🌾 Villager</span>
                <p style="font-size:.8rem; margin-top:.35rem; color:var(--muted);">Votes during the day to find the wolves.</p>
            </div>
            @if($game->players->where('role','Seer')->count())
            <div>
                <span class="role-chip role-Seer">🔮 Seer</span>
                <p style="font-size:.8rem; margin-top:.35rem; color:var(--muted);">Peeks at one player's role each night.</p>
            </div>
            @endif
            @if($game->players->where('role','Doctor')->count())
            <div>
                <span class="role-chip role-Doctor">💊 Doctor</span>
                <p style="font-size:.8rem; margin-top:.35rem; color:var(--muted);">Protects one player from elimination each night.</p>
            </div>
            @endif
        </div>
    </div>
@endif

<script>
function copyText(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = '✓ Copied';
        setTimeout(() => btn.textContent = 'Copy', 1500);
    });
}
</script>
@endsection
