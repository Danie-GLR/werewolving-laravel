@extends('layouts.app')
@section('title', $game->name)
@section('content')
<style>
    /* Phase banner */
    .phase-banner { border-radius:var(--radius); padding:1.1rem 1.5rem; text-align:center; margin-bottom:1.4rem; box-shadow:var(--shadow); }
    .phase-night  { background:linear-gradient(135deg,#1a1040,#2d1f6e); border:2px solid #5b4fcf; }
    .phase-day    { background:linear-gradient(135deg,#3d2800,#6b4800); border:2px solid #f5c842; }
    .phase-fin    { background:linear-gradient(135deg,#0d2d18,#1b5e35); border:2px solid #6ee7a0; }
    .phase-label  { font-family:'Fredoka One',cursive; font-size:1.8rem; }
    .phase-sub    { color:var(--muted); font-size:.88rem; font-weight:600; margin-top:.2rem; }

    /* Stats row */
    .stats       { display:flex; gap:.75rem; margin-bottom:1.3rem; flex-wrap:wrap; }
    .stat        { background:var(--surface); border-radius:var(--radius-sm); padding:.7rem 1rem; flex:1; min-width:72px; text-align:center; box-shadow:var(--shadow); }
    .stat .val   { font-family:'Fredoka One',cursive; font-size:1.8rem; }
    .stat .lbl   { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-top:.1rem; }

    /* My role card */
    .my-role-card { border-radius:var(--radius); padding:1.4rem 1.2rem; display:flex; align-items:center; gap:1.2rem; margin-bottom:1.2rem; box-shadow:var(--shadow); }
    .mrc-icon     { font-size:3.5rem; flex-shrink:0; }
    .mrc-body     {}
    .mrc-title    { font-family:'Fredoka One',cursive; font-size:1.5rem; margin-bottom:.2rem; }
    .mrc-desc     { color:rgba(255,255,255,.7); font-size:.88rem; font-weight:600; line-height:1.5; }
    .mrc-Werewolf { background:linear-gradient(135deg,#7b1515,#c0392b); border:2px solid #e74c3c; }
    .mrc-Villager { background:linear-gradient(135deg,#1b5e1b,#27ae60); border:2px solid #6abf4b; }
    .mrc-Seer     { background:linear-gradient(135deg,#3d1060,#8e44ad); border:2px solid #a855f7; }
    .mrc-Doctor   { background:linear-gradient(135deg,#0d2d5c,#2980b9); border:2px solid #3b82f6; }

    /* Action section */
    .action-box   { background:var(--surface); border-radius:var(--radius); padding:1.2rem; margin-bottom:1rem; box-shadow:var(--shadow); }
    .action-title { font-family:'Fredoka One',cursive; font-size:1.15rem; margin-bottom:.75rem; }
    .vote-grid    { display:flex; flex-wrap:wrap; gap:.5rem; }
    .vote-btn     { background:var(--surface2); border:2px solid transparent; color:#fff; font-family:'Nunito',sans-serif; font-weight:800; font-size:.9rem; padding:.45rem 1rem; border-radius:50px; cursor:pointer; transition:all .15s; }
    .vote-btn:hover { background:var(--pink); border-color:var(--pink-dark); transform:translateY(-1px); }
    .voted-label  { display:inline-flex; align-items:center; gap:.4rem; background:#1b5e35; border:2px solid #6ee7a0; border-radius:50px; padding:.4rem 1rem; font-weight:800; font-size:.9rem; color:#6ee7a0; }

    /* Player grid */
    .p-grid       { display:grid; grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); gap:.65rem; margin:1rem 0; }
    .p-card       { background:var(--surface); border-radius:var(--radius-sm); padding:.85rem .6rem; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,.2); transition:opacity .2s; }
    .p-card.dead  { opacity:.3; }
    .p-card .av   { font-size:2rem; margin-bottom:.3rem; }
    .p-card .pn   { font-weight:800; font-size:.8rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .you-dot      { display:inline-block; width:6px; height:6px; background:var(--pink); border-radius:50%; margin-left:3px; vertical-align:middle; }

    /* GM bar */
    .gm-bar       { background:var(--surface); border:2px solid var(--pink); border-radius:var(--radius); padding:1rem 1.2rem; margin-bottom:1.1rem; display:flex; gap:.75rem; flex-wrap:wrap; align-items:center; box-shadow:var(--shadow); }
    .gm-badge     { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; color:var(--pink); }
    .tally        { font-size:.82rem; font-weight:700; color:var(--muted); }

    /* Peek flash */
    .peek-flash   { background:linear-gradient(135deg,#2d0d4d,#5b21b6); border:2px solid #a855f7; border-radius:var(--radius-sm); padding:.85rem 1rem; color:#e9d5ff; font-weight:700; margin-bottom:1rem; box-shadow:var(--shadow); }

    /* Sleep screen */
    .sleep-box    { text-align:center; padding:2rem 1rem; }
    .sleep-icon   { font-size:3.5rem; margin-bottom:.5rem; }
</style>

{{-- Peek flash --}}
@if(session('peek_result'))
    <div class="peek-flash">{{ session('peek_result') }}</div>
@endif

@php
    $alive   = $game->players->where('is_alive',true);
    $wolves  = $alive->where('role','Werewolf')->count();
    $village = $alive->whereNotIn('role',['Werewolf'])->count();
    $phase   = $game->phase;
    $status  = $game->status;
@endphp

{{-- Phase banner --}}
@if($status === 'finished')
    @php $winner = ($wolves === 0) ? 'villagers' : 'werewolves'; @endphp
    <div class="phase-banner phase-fin">
        <div class="phase-label">{{ $winner === 'villagers' ? '🎉 Villagers Win!' : '🐺 Werewolves Win!' }}</div>
        <div class="phase-sub">{{ $game->name }} · {{ $game->round }} round(s) played</div>
    </div>
@else
    <div class="phase-banner {{ $phase === 'night' ? 'phase-night' : 'phase-day' }}">
        <div class="phase-label">{{ $phase === 'night' ? '🌙 Night' : '☀️ Day' }} — Round {{ $game->round }}</div>
        <div class="phase-sub">{{ $game->name }}</div>
    </div>
@endif

{{-- Stats --}}
<div class="stats">
    <div class="stat"><div class="val" style="color:#6ee7a0;">{{ $alive->count() }}</div><div class="lbl">Alive</div></div>
    <div class="stat"><div class="val" style="color:#fca5a5;">{{ $wolves }}</div><div class="lbl">Wolves</div></div>
    <div class="stat"><div class="val" style="color:#86efac;">{{ $village }}</div><div class="lbl">Village</div></div>
    <div class="stat"><div class="val" style="color:#888;">{{ $game->players->where('is_alive',false)->count() }}</div><div class="lbl">Dead</div></div>
</div>

{{-- GM controls --}}
@if($isGM && $status === 'in_progress')
    <div class="gm-bar">
        <span class="gm-badge">🎮 GM</span>
        @if($phase === 'night')
            @php $pw = $game->pendingWerewolfVotes(); @endphp
            <form method="POST" action="{{ route('games.resolve-night', $game) }}">
                @csrf <button type="submit" class="btn btn-yellow-fill">☀️ Resolve Night</button>
            </form>
            <span class="tally">🐺 {{ $wolves - $pw }}/{{ $wolves }} wolves voted
                @if($game->doctor_save_id) · 💊 Doc saved @endif
                @if($game->seer_peek_id)   · 🔮 Seer peeked @endif
            </span>
        @else
            @php $pd = $game->pendingDayVotes(); @endphp
            <form method="POST" action="{{ route('games.resolve-day', $game) }}">
                @csrf <button type="submit" class="btn btn-yellow-fill">🌙 Force Resolve</button>
            </form>
            <span class="tally">☀️ {{ $alive->count() - $pd }}/{{ $alive->count() }} voted</span>
        @endif
    </div>
@endif

{{-- My role card --}}
@if($myPlayer)
    <div class="my-role-card mrc-{{ $myPlayer->role ?? 'Villager' }}">
        <div class="mrc-icon">{{ $myPlayer->role_icon }}</div>
        <div class="mrc-body">
            <div class="mrc-title">
                {{ $myPlayer->name }} — {{ $myPlayer->role }}
                @if(!$myPlayer->is_alive) <span style="color:#fca5a5;">☠️</span>@endif
            </div>
            <div class="mrc-desc">
                @switch($myPlayer->role)
                    @case('Werewolf') Wake each night to eliminate a villager. Win when wolves ≥ village. @break
                    @case('Villager') Vote out the wolves during the day. Trust no one. @break
                    @case('Seer')     Peek at one player's role each night. Guide the village wisely. @break
                    @case('Doctor')   Protect one player from the wolves each night. @break
                @endswitch
            </div>
        </div>
    </div>
@endif

{{-- Night actions --}}
@if($myPlayer && $myPlayer->is_alive && $status === 'in_progress' && $phase === 'night')

    @if($myPlayer->role === 'Werewolf')
        <div class="action-box">
            <div class="action-title">🐺 Choose tonight's victim</div>
            @php $fw = $alive->where('role','Werewolf')->where('id','!=',$myPlayer->id); @endphp
            @if($fw->count())<p style="color:#fca5a5;font-size:.82rem;font-weight:700;margin-bottom:.6rem;">Fellow wolves: {{ $fw->pluck('name')->join(', ') }}</p>@endif
            @if($myPlayer->night_vote_target_id)
                @php $vt = $game->players->firstWhere('id',$myPlayer->night_vote_target_id); @endphp
                <span class="voted-label">✅ Voted for {{ $vt?->name }}</span>
            @else
                <div class="vote-grid">
                    @foreach($alive->whereNotIn('role',['Werewolf']) as $t)
                        <form method="POST" action="{{ route('games.night-vote', $game) }}">
                            @csrf <input type="hidden" name="target_id" value="{{ $t->id }}">
                            <button type="submit" class="vote-btn">{{ $t->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($myPlayer->role === 'Doctor')
        <div class="action-box">
            <div class="action-title">💊 Protect someone tonight</div>
            @if($game->doctor_save_id)
                @php $sv = $game->players->firstWhere('id',$game->doctor_save_id); @endphp
                <span class="voted-label">✅ Protecting {{ $sv?->name }}</span>
            @else
                <div class="vote-grid">
                    @foreach($alive as $t)
                        <form method="POST" action="{{ route('games.doctor-save', $game) }}">
                            @csrf <input type="hidden" name="target_id" value="{{ $t->id }}">
                            <button type="submit" class="vote-btn">{{ $t->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($myPlayer->role === 'Seer')
        <div class="action-box">
            <div class="action-title">🔮 Peek at someone's role</div>
            @if($myPlayer->has_peeked)
                <span class="voted-label">✅ Vision used this round</span>
            @else
                <div class="vote-grid">
                    @foreach($alive->where('id','!=',$myPlayer->id) as $t)
                        <form method="POST" action="{{ route('games.seer-peek', $game) }}">
                            @csrf <input type="hidden" name="target_id" value="{{ $t->id }}">
                            <button type="submit" class="vote-btn">{{ $t->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($myPlayer->role === 'Villager')
        <div class="action-box sleep-box">
            <div class="sleep-icon">😴</div>
            <p style="color:var(--muted);font-weight:700;">It's night. Sleep tight.<br>Voting opens at dawn.</p>
        </div>
    @endif

{{-- Day actions --}}
@elseif($myPlayer && $myPlayer->is_alive && $status === 'in_progress' && $phase === 'day')
    <div class="action-box">
        <div class="action-title">☀️ Vote to eliminate a suspect</div>
        @if($myPlayer->day_vote_target_id)
            @php $dv = $game->players->firstWhere('id',$myPlayer->day_vote_target_id); @endphp
            <span class="voted-label">✅ Voted for {{ $dv?->name }} — waiting for others…</span>
        @else
            <div class="vote-grid">
                @foreach($alive->where('id','!=',$myPlayer->id) as $t)
                    <form method="POST" action="{{ route('games.day-vote', $game) }}">
                        @csrf <input type="hidden" name="target_id" value="{{ $t->id }}">
                        <button type="submit" class="vote-btn">{{ $t->name }}</button>
                    </form>
                @endforeach
            </div>
        @endif
    </div>

@elseif($myPlayer && !$myPlayer->is_alive && $status === 'in_progress')
    <div class="action-box sleep-box">
        <div class="sleep-icon">☠️</div>
        <p style="color:#fca5a5;font-weight:800;">You've been eliminated.</p>
        <p style="color:var(--muted);font-size:.88rem;font-weight:600;margin-top:.3rem;">Spectate the rest of the game.</p>
    </div>
@endif

{{-- Player grid --}}
<h2 style="margin-bottom:.6rem;">Players</h2>
<div class="p-grid">
    @foreach($game->players as $i => $p)
        <div class="p-card {{ !$p->is_alive ? 'dead' : '' }}">
            <div class="av">
                @if($status === 'finished' || ($myPlayer && $myPlayer->id === $p->id))
                    {{ $p->role_icon }}
                @elseif($myPlayer && $myPlayer->role === 'Werewolf' && $p->role === 'Werewolf')
                    🐺
                @else
                    {{ $p->is_alive ? '👤' : '💀' }}
                @endif
            </div>
            <div class="pn">
                {{ $p->name }}
                @if($myPlayer && $myPlayer->id === $p->id)<span class="you-dot"></span>@endif
            </div>
            @if($status === 'finished')
                <div style="margin-top:.3rem;">
                    <span class="role-pill rp-{{ $p->role }}" style="font-size:.65rem;">{{ $p->role }}</span>
                </div>
            @endif
            @if(!$p->is_alive)<div style="font-size:.65rem;color:#e94560;margin-top:.2rem;">☠️</div>@endif
        </div>
    @endforeach
</div>

@if($status === 'in_progress')
    <meta http-equiv="refresh" content="6">
    <p style="text-align:center;color:#555;font-size:.72rem;margin-top:1rem;font-weight:600;">Auto-refreshes every 6 seconds</p>
@endif
@endsection
