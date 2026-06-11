@extends('layouts.app')

@section('title', $game->name)

@section('content')
<style>
    /* ── Phase header ── */
    .phase-header{text-align:center;padding:1.2rem 1rem 1rem;border-radius:10px;margin-bottom:1.5rem;}
    .phase-night  {background:#0f0b2d;border:1px solid #4f46e5;}
    .phase-day    {background:#1c1800;border:1px solid #d97706;}
    .phase-fin    {background:#0d1f0d;border:1px solid #22c55e;}
    .phase-label  {font-size:1.6rem;font-weight:bold;}
    .phase-sub    {color:#9ca3af;margin-top:.2rem;font-size:.9rem;}

    /* ── Role card ── */
    .role-card{border-radius:10px;padding:1.5rem;text-align:center;margin-bottom:1.2rem;}
    .role-icon-big{font-size:4rem;margin-bottom:.5rem;}
    .role-title{font-size:1.4rem;font-weight:bold;margin-bottom:.4rem;}
    .role-desc{color:#9ca3af;font-size:.9rem;line-height:1.6;}
    .role-Werewolf{background:#2d0d0d;border:2px solid #e94560;}
    .role-Villager{background:#0d2d18;border:2px solid #22c55e;}
    .role-Seer    {background:#1a0d2d;border:2px solid #a855f7;}
    .role-Doctor  {background:#0d1a2d;border:2px solid #3b82f6;}

    /* ── Action section ── */
    .action-section{background:#16213e;border-radius:8px;padding:1.2rem;margin-bottom:1rem;}
    .action-section h2{margin-top:0;font-size:1.05rem;}
    .vote-grid{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.75rem;}
    .vote-btn{background:#0f3460;border:1px solid #334155;color:#eee;padding:.5rem .9rem;
               border-radius:6px;cursor:pointer;font-size:.9rem;transition:background .15s;}
    .vote-btn:hover{background:#e94560;border-color:#e94560;}
    .vote-btn.voted{background:#16a34a;border-color:#16a34a;cursor:default;}

    /* ── Stats ── */
    .stats{display:flex;gap:.75rem;margin-bottom:1.2rem;flex-wrap:wrap;}
    .stat{background:#16213e;border-radius:8px;padding:.7rem 1rem;flex:1;min-width:80px;text-align:center;}
    .stat .value{font-size:1.6rem;font-weight:bold;}
    .stat .label{font-size:.7rem;color:#9ca3af;margin-top:.1rem;}

    /* ── Player grid ── */
    .player-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:.6rem;margin:1rem 0;}
    .player-card{background:#0f172a;border:1px solid #1e3a5f;border-radius:8px;padding:.8rem .6rem;text-align:center;}
    .player-card.dead{opacity:.35;}
    .player-card .av{font-size:1.6rem;margin-bottom:.3rem;}
    .player-card .pn{font-size:.82rem;font-weight:bold;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .you-dot{width:6px;height:6px;background:#e94560;border-radius:50%;display:inline-block;margin-left:4px;vertical-align:middle;}

    /* ── GM bar ── */
    .gm-bar{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:1rem 1.2rem;
             margin-bottom:1rem;display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;}
    .btn-yellow{background:#d97706;color:#fff;border:none;padding:.5rem 1.2rem;border-radius:4px;cursor:pointer;font-size:.9rem;}
    .btn-yellow:hover{background:#b45309;}
    .tally-note{font-size:.8rem;color:#9ca3af;}

    /* ── Peek alert ── */
    .alert-peek{background:#1a0d2d;border:1px solid #a855f7;border-radius:6px;
                 padding:.75rem 1rem;color:#e9d5ff;margin-bottom:1rem;}
    .role-chip{font-size:.72rem;padding:.15rem .5rem;border-radius:10px;display:inline-block;margin-top:.25rem;}
    .role-chip.role-Werewolf{background:#4d0b0b;color:#fca5a5;}
    .role-chip.role-Villager{background:#0d2d18;color:#86efac;}
    .role-chip.role-Seer    {background:#2d1a4d;color:#d8b4fe;}
    .role-chip.role-Doctor  {background:#0d1a3d;color:#93c5fd;}
</style>

{{-- Peek result flash --}}
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
    @php
        $winner = $game->checkWinner() ?? ($wolves === 0 ? 'villagers' : 'werewolves');
    @endphp
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
    <div class="stat"><div class="value" style="color:#86efac;">{{ $alive->count() }}</div><div class="label">Alive</div></div>
    <div class="stat"><div class="value" style="color:#fca5a5;">{{ $wolves }}</div><div class="label">Wolves 🐺</div></div>
    <div class="stat"><div class="value" style="color:#86efac;">{{ $village }}</div><div class="label">Village</div></div>
    <div class="stat"><div class="value" style="color:#9ca3af;">{{ $game->players->where('is_alive',false)->count() }}</div><div class="label">Dead ☠️</div></div>
</div>

{{-- ── GM controls ── --}}
@if($isGM && $status === 'in_progress')
    <div class="gm-bar">
        @if($phase === 'night')
            @php $pendingW = $game->pendingWerewolfVotes(); @endphp
            <form method="POST" action="{{ route('games.resolve-night', $game) }}">
                @csrf
                <button type="submit" class="btn btn-yellow">☀️ Resolve Night</button>
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
                <button type="submit" class="btn btn-yellow">🌙 Resolve Day (force)</button>
            </form>
            <span class="tally-note">☀️ {{ $alive->count() - $pendingD }}/{{ $alive->count() }} voted</span>
        @endif
    </div>
@endif

{{-- ── MY ROLE CARD ── --}}
@if($myPlayer)
    <div class="role-card role-{{ $myPlayer->role ?? 'Villager' }}">
        <div class="role-icon-big">{{ $myPlayer->role_icon }}</div>
        <div class="role-title">
            You are {{ $myPlayer->name }} — <strong>{{ $myPlayer->role }}</strong>
            @if(!$myPlayer->is_alive)<span style="color:#e94560;"> (☠️ Eliminated)</span>@endif
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

{{-- ── NIGHT ACTIONS ── --}}
@if($myPlayer && $myPlayer->is_alive && $status === 'in_progress' && $phase === 'night')

    {{-- Werewolf kill vote --}}
    @if($myPlayer->role === 'Werewolf')
        <div class="action-section">
            <h2>🐺 Choose tonight's victim</h2>
            @if($myPlayer->night_vote_target_id)
                @php $vt = $game->players->firstWhere('id', $myPlayer->night_vote_target_id); @endphp
                <p style="color:#86efac;">✅ You voted for <strong>{{ $vt?->name }}</strong>.</p>
                @php $fellowWolves = $game->players->where('role','Werewolf')->where('is_alive',true)->where('id','!=',$myPlayer->id); @endphp
                @if($fellowWolves->count())
                    <p style="color:#fca5a5;font-size:.82rem;">Fellow wolves: {{ $fellowWolves->pluck('name')->join(', ') }}</p>
                @endif
            @else
                @php $fellowWolves = $game->players->where('role','Werewolf')->where('is_alive',true)->where('id','!=',$myPlayer->id); @endphp
                @if($fellowWolves->count())
                    <p style="color:#fca5a5;font-size:.82rem;">Fellow wolves: {{ $fellowWolves->pluck('name')->join(', ') }}</p>
                @endif
                <p style="color:#9ca3af;font-size:.85rem;">Pick a non-wolf target:</p>
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

    {{-- Doctor save --}}
    @if($myPlayer->role === 'Doctor')
        <div class="action-section">
            <h2>💊 Protect someone tonight</h2>
            @if($game->doctor_save_id)
                @php $sv = $game->players->firstWhere('id', $game->doctor_save_id); @endphp
                <p style="color:#86efac;">✅ Protecting <strong>{{ $sv?->name }}</strong> tonight.</p>
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

    {{-- Seer peek --}}
    @if($myPlayer->role === 'Seer')
        <div class="action-section">
            <h2>🔮 Peek at someone's role</h2>
            @if($myPlayer->has_peeked)
                <p style="color:#86efac;">✅ You've used your vision this round.</p>
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

    {{-- Villager waiting screen --}}
    @if($myPlayer->role === 'Villager')
        <div class="action-section" style="text-align:center;padding:1.5rem;">
            <div style="font-size:2.5rem;">😴</div>
            <p style="color:#9ca3af;margin-top:.5rem;">It's night. Sleep tight.<br>Actions happen in the morning.</p>
        </div>
    @endif

{{-- ── DAY ACTIONS ── --}}
@elseif($myPlayer && $myPlayer->is_alive && $status === 'in_progress' && $phase === 'day')
    <div class="action-section">
        <h2>☀️ Vote to eliminate a suspect</h2>
        @if($myPlayer->day_vote_target_id)
            @php $dv = $game->players->firstWhere('id', $myPlayer->day_vote_target_id); @endphp
            <p style="color:#86efac;">✅ You voted for <strong>{{ $dv?->name }}</strong>. Waiting for others…</p>
        @else
            <p style="color:#9ca3af;font-size:.85rem;">Who do you think is a werewolf?</p>
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

{{-- Eliminated player ── --}}
@elseif($myPlayer && !$myPlayer->is_alive && $status === 'in_progress')
    <div class="action-section" style="text-align:center;padding:1.5rem;">
        <div style="font-size:2.5rem;">☠️</div>
        <p style="color:#e94560;font-weight:bold;margin-top:.5rem;">You've been eliminated.</p>
        <p style="color:#9ca3af;font-size:.85rem;">Spectate the rest of the game.</p>
    </div>
@endif

{{-- ── Player grid (no roles shown unless game over or GM) ── --}}
<h2 style="margin-bottom:.5rem;">Players</h2>
<div class="player-grid">
    @foreach($game->players as $p)
        <div class="player-card {{ !$p->is_alive ? 'dead' : '' }}">
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
                @if($myPlayer && $myPlayer->id === $p->id)
                    <span class="you-dot" title="you"></span>
                @endif
            </div>
            @if($status === 'finished')
                <span class="role-chip role-{{ $p->role }}">{{ $p->role }}</span>
            @endif
            @if(!$p->is_alive)
                <div style="font-size:.68rem;color:#e94560;margin-top:.2rem;">☠️</div>
            @endif
        </div>
    @endforeach
</div>

{{-- Auto-refresh every 6s so everyone sees phase changes --}}
@if($status === 'in_progress')
    <meta http-equiv="refresh" content="6">
    <p style="text-align:center;color:#4b5563;font-size:.72rem;margin-top:1rem;">Refreshes every 6 seconds</p>
@endif

@endsection
