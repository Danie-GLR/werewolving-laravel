@extends('layouts.app')

@section('title', $game->name)

@section('content')
<style>
    /* ── Phase header ── */
    .phase-header {
        text-align: center;
        padding: 1.2rem 1rem 1rem;
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
    }

    .phase-night  { background: rgba(79,70,229,0.1);  border: 1px solid rgba(79,70,229,0.25); }
    .phase-day    { background: rgba(255,209,102,0.08); border: 1px solid rgba(217,119,6,0.25); }
    .phase-fin    { background: rgba(45,206,137,0.08); border: 1px solid rgba(45,206,137,0.25); }

    .phase-label { font-size: 1.6rem; font-weight: 900; }
    .phase-sub   { color: var(--muted); margin-top: .25rem; font-size: .9rem; font-weight: 600; }

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

    .role-card::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: .06;
        background-size: cover;
        background-position: center;
        pointer-events: none;
    }

    .role-Werewolf { background: rgba(233,69,96,0.1);  border: 2px solid rgba(233,69,96,0.35); }
    .role-Villager { background: rgba(45,206,137,0.08); border: 2px solid rgba(45,206,137,0.3); }
    .role-Seer     { background: rgba(168,85,247,0.1);  border: 2px solid rgba(168,85,247,0.3); }
    .role-Doctor   { background: rgba(59,130,246,0.08); border: 2px solid rgba(59,130,246,0.3); }

    .role-card-img {
        width: 100px;
        height: 100px;
        object-fit: contain;
        filter: drop-shadow(0 6px 20px rgba(0,0,0,0.5));
        margin-bottom: .75rem;
    }

    .role-title {
        font-size: 1.3rem;
        font-weight: 900;
        margin-bottom: .5rem;
    }

    .role-desc { color: var(--muted); font-size: .9rem; line-height: 1.6; font-weight: 600; }

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
    }

    .player-card:hover { border-color: rgba(255,61,127,0.3); }
    .player-card.dead  { opacity: .35; }

    .player-card .av { font-size: 1.6rem; margin-bottom: .35rem; }
    .player-card .av img { width: 40px; height: 40px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4)); }
    .player-card .pn { font-size: .8rem; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

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

    .refresh-note {
        text-align: center;
        color: rgba(155,135,184,0.35);
        font-size: .72rem;
        margin-top: 1rem;
    }
</style>

@php
    function roleImg($role) {
        $map = [
            'Werewolf' => 'icon_werewolf.png',
            'Villager' => 'icon_villager.png',
            'Seer'     => 'icon_seer.png',
            'Doctor'   => 'icon_doctor.png',
        ];
        return asset('images/' . ($map[$role] ?? 'icon_villager.png'));
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
@endphp

{{-- Phase header --}}
@if($status === 'finished')
    @php $winner = $game->checkWinner() ?? ($wolves === 0 ? 'villagers' : 'werewolves'); @endphp
    <div class="phase-header phase-fin">
        <div class="phase-label">{{ $winner === 'villagers' ? '🎉 Villagers Win!' : '🐺 Werewolves Win!' }}</div>
        <div class="phase-sub">{{ $game->name }} · Game over after {{ $game->round }} round(s)</div>
    </div>
@else
    <div class="phase-header {{ $phase === 'night' ? 'phase-night' : 'phase-day' }}">
        <div class="phase-label">{{ $phase === 'night' ? '🌙 Night' : '☀️ Day' }} — Round {{ $game->round }}</div>
        <div class="phase-sub">{{ $game->name }}</div>
    </div>
@endif

{{-- Stats --}}
<div class="stats">
    <div class="stat"><div class="value" style="color:var(--green);">{{ $alive->count() }}</div><div class="label">Alive</div></div>
    <div class="stat"><div class="value" style="color:var(--red);">{{ $wolves }}</div><div class="label">Wolves</div></div>
    <div class="stat"><div class="value" style="color:var(--green);">{{ $village }}</div><div class="label">Village</div></div>
    <div class="stat"><div class="value" style="color:var(--muted);">{{ $game->players->where('is_alive',false)->count() }}</div><div class="label">Dead</div></div>
</div>

{{-- GM controls --}}
@if($isGM && $status === 'in_progress')
    <div class="gm-bar">
        @if($phase === 'night')
            @php $pendingW = $game->pendingWerewolfVotes(); @endphp
            <form method="POST" action="{{ route('games.resolve-night', $game) }}">
                @csrf
                <button type="submit" class="btn-yellow">☀️ Resolve Night</button>
            </form>
            <span class="tally-note">
                🐺 {{ $wolves - $pendingW }}/{{ $wolves }} wolves voted
                @if($game->doctor_save_id) · 💊 Doctor saved @endif
                @if($game->seer_peek_id)   · 🔮 Seer peeked @endif
            </span>
        @else
            @php $pendingD = $game->pendingDayVotes(); @endphp
            <form method="POST" action="{{ route('games.resolve-day', $game) }}">
                @csrf
                <button type="submit" class="btn-yellow">🌙 Resolve Day (force)</button>
            </form>
            <span class="tally-note">☀️ {{ $alive->count() - $pendingD }}/{{ $alive->count() }} voted</span>
        @endif
    </div>
@endif

{{-- Role card --}}
@if($myPlayer)
    <div class="role-card role-{{ $myPlayer->role ?? 'Villager' }}">
        <img src="{{ roleImg($myPlayer->role) }}" alt="{{ $myPlayer->role }}" class="role-card-img">
        <div class="role-title">
            {{ $myPlayer->name }} — <strong>{{ $myPlayer->role }}</strong>
            @if(!$myPlayer->is_alive)<span style="color:var(--red);"> (Eliminated)</span>@endif
        </div>
        <div class="role-desc">
            @switch($myPlayer->role)
                @case('Werewolf') Wake each night to eliminate a villager. Win when wolves ≥ village. @break
                @case('Villager') Vote out the wolves during the day. Trust no one. @break
                @case('Seer')     Peek at one player's role each night. Guide the village carefully. @break
                @case('Doctor')   Protect one player from the wolves each night. @break
            @endswitch
        </div>
    </div>
@endif

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

    @if($myPlayer->role === 'Villager')
        <div class="action-section" style="text-align:center; padding:2rem;">
            <div style="font-size:2.5rem; margin-bottom:.5rem;">😴</div>
            <p style="color:var(--muted); font-weight:600;">It's night. Sleep tight.<br>Actions happen in the morning.</p>
        </div>
    @endif

{{-- Day actions --}}
@elseif($myPlayer && $myPlayer->is_alive && $status === 'in_progress' && $phase === 'day')
    <div class="action-section">
        <h2>☀️ Vote to eliminate a suspect</h2>
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
<div class="section-title">Players</div>
<div class="player-grid">
    @foreach($game->players as $p)
        <div class="player-card {{ !$p->is_alive ? 'dead' : '' }}">
            <div class="av">
                @if($status === 'finished' || ($myPlayer && $myPlayer->id === $p->id))
                    <img src="{{ roleImg($p->role) }}" alt="{{ $p->role }}">
                @elseif($myPlayer && $myPlayer->role === 'Werewolf' && $p->role === 'Werewolf')
                    <img src="{{ asset('images/icon_werewolf.png') }}" alt="Werewolf">
                @else
                    {{ $p->is_alive ? '👤' : '💀' }}
                @endif
            </div>
            <div class="pn">
                {{ $p->name }}
                @if($myPlayer && $myPlayer->id === $p->id)
                    <span class="you-dot" title="you"></span>
                @endif
            </div>
            @if($status === 'finished')
                <span class="role-chip role-{{ $p->role }}">{{ $p->role }}</span>
            @endif
            @if(!$p->is_alive)
                <div style="font-size:.65rem; color:var(--red); margin-top:.2rem; font-weight:800;">☠️ Out</div>
            @endif
        </div>
    @endforeach
</div>

@if($status === 'in_progress')
    <meta http-equiv="refresh" content="6">
    <p class="refresh-note">Refreshes every 6 seconds</p>
@endif
@endsection
