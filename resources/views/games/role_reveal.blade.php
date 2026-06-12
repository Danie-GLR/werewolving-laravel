@extends('layouts.app')

@section('title', 'Your Role — ' . $game->name)

@section('content')
<style>
    @php
        $roleImgMap = [
            'Werewolf' => 'icon_werewolf.png',
            'Villager' => 'icon_villager.png',
            'Seer'     => 'icon_seer.png',
            'Doctor'   => 'icon_doctor.png',
        ];
        $roleImgFile = $roleImgMap[$player->role] ?? 'icon_villager.png';
    @endphp

    .role-reveal-card {
        text-align: center;
        padding: 2.5rem 2rem;
        border-radius: var(--radius);
        max-width: 480px;
        margin: 0 auto 1.5rem;
    }

    .role-Werewolf { background: rgba(233,69,96,0.1);  border: 2px solid rgba(233,69,96,0.4); }
    .role-Villager { background: rgba(45,206,137,0.08); border: 2px solid rgba(45,206,137,0.35); }
    .role-Seer     { background: rgba(168,85,247,0.1);  border: 2px solid rgba(168,85,247,0.35); }
    .role-Doctor   { background: rgba(59,130,246,0.08); border: 2px solid rgba(59,130,246,0.35); }

    .role-reveal-img {
        width: 120px;
        height: 120px;
        object-fit: contain;
        filter: drop-shadow(0 8px 24px rgba(0,0,0,0.5));
        margin-bottom: 1rem;
        animation: revealPop .4s cubic-bezier(.34,1.56,.64,1) both;
    }

    @keyframes revealPop {
        from { transform: scale(0.5); opacity: 0; }
        to   { transform: scale(1);   opacity: 1; }
    }

    .role-reveal-title {
        font-size: 1.7rem;
        font-weight: 900;
        letter-spacing: -.5px;
        margin-bottom: .5rem;
    }

    .role-reveal-name {
        color: var(--muted);
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .role-reveal-desc {
        color: var(--muted);
        font-size: .95rem;
        line-height: 1.65;
        font-weight: 600;
        margin-bottom: 1.2rem;
    }

    .phase-badge {
        display: inline-block;
        padding: .3rem 1rem;
        border-radius: 20px;
        font-size: .82rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-bottom: 1.5rem;
    }

    .phase-night { background: rgba(79,70,229,0.15);   color: #a5b4fc; border: 1px solid rgba(79,70,229,0.3); }
    .phase-day   { background: rgba(217,119,6,0.12);   color: var(--yellow); border: 1px solid rgba(217,119,6,0.3); }

    .alert-peek {
        background: rgba(168,85,247,0.1);
        border: 1px solid rgba(168,85,247,0.3);
        border-radius: var(--radius-sm);
        padding: .8rem 1.2rem;
        color: #e9d5ff;
        margin-bottom: 1rem;
        font-weight: 700;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
    }

    .action-section {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-bottom: 1rem;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
    }

    .action-section h2 { margin-top: 0; margin-bottom: 1rem; font-size: 1.05rem; font-weight: 900; }

    .vote-grid { display: flex; flex-wrap: wrap; gap: .5rem; }

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

    .vote-btn:hover { background: var(--pink); border-color: var(--pink); }

    .nav-link {
        display: block;
        text-align: center;
        margin-top: 1rem;
        color: var(--muted);
        text-decoration: none;
        font-weight: 700;
        font-size: .9rem;
        transition: color .2s;
    }

    .nav-link:hover { color: var(--text); }
</style>

@if(session('peek_result'))
    <div class="alert-peek">{{ session('peek_result') }}</div>
@endif

{{-- Phase badge --}}
<div style="text-align:center; margin-bottom:1rem;">
    @if($game->status === 'in_progress')
        <span class="phase-badge {{ $game->phase === 'night' ? 'phase-night' : 'phase-day' }}">
            {{ $game->phase === 'night' ? '🌙 Night — Round ' . $game->round : '☀️ Day — Round ' . $game->round }}
        </span>
    @elseif($game->status === 'finished')
        <span class="phase-badge" style="background:rgba(45,206,137,0.12); color:var(--green); border:1px solid rgba(45,206,137,0.3);">✅ Game Over</span>
    @else
        <span class="phase-badge" style="background:rgba(155,135,184,0.1); color:var(--muted); border:1px solid var(--border);">⏳ Waiting to start</span>
    @endif
</div>

{{-- Role reveal card --}}
<div class="role-reveal-card role-{{ $player->role ?? 'Villager' }}">
    <img src="{{ asset('images/' . $roleImgFile) }}" alt="{{ $player->role }}" class="role-reveal-img">
    <div class="role-reveal-name">{{ $player->name }}, you are</div>
    <div class="role-reveal-title">{{ $player->role ?? '?' }}</div>
    <div class="role-reveal-desc">
        @switch($player->role)
            @case('Werewolf')
                You wake each night to eliminate a villager. Work with your fellow wolves.
                Win when werewolves equal or outnumber the village.
                @break
            @case('Villager')
                You have no special power — only your wits. Vote out the wolves during the day.
                @break
            @case('Seer')
                Each night you can peek at one player's true role. Guide the village carefully — don't reveal yourself too soon.
                @break
            @case('Doctor')
                Each night you protect one player from the werewolves' kill. You can protect yourself once.
                @break
        @endswitch
    </div>
    @if(!$player->is_alive)
        <div style="color:var(--red); font-weight:800;">☠️ You have been eliminated</div>
    @endif
</div>

{{-- Actions --}}
@if($game->status === 'in_progress' && $player->is_alive)

    @if($player->role === 'Werewolf' && $game->phase === 'night')
        <div class="action-section">
            <h2>🐺 Choose your victim tonight</h2>
            @if($player->night_vote_target_id)
                @php $alreadyTarget = $game->players->firstWhere('id', $player->night_vote_target_id); @endphp
                <p style="color:var(--green); font-weight:700;">✅ You voted for <strong>{{ $alreadyTarget?->name }}</strong>. Waiting for other wolves…</p>
            @else
                @php $wolves = $game->players->where('role','Werewolf')->where('is_alive',true)->where('id','!=',$player->id); @endphp
                @if($wolves->count())
                    <p style="color:#fca5a5; font-size:.85rem; font-weight:600; margin-bottom:.75rem;">Fellow wolves: {{ $wolves->pluck('name')->join(', ') }}</p>
                @endif
                <p style="color:var(--muted); font-size:.9rem; font-weight:600; margin-bottom:.75rem;">Pick a non-wolf player to eliminate:</p>
                <div class="vote-grid">
                    @foreach($game->players->where('is_alive',true)->where('role','!=','Werewolf') as $target)
                        <form method="POST" action="{{ route('games.night-vote', $game) }}">
                            @csrf
                            <input type="hidden" name="token"     value="{{ $player->token }}">
                            <input type="hidden" name="target_id" value="{{ $target->id }}">
                            <button type="submit" class="vote-btn">{{ $target->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($player->role === 'Doctor' && $game->phase === 'night')
        <div class="action-section">
            <h2>💊 Protect someone tonight</h2>
            @if($game->doctor_save_id)
                @php $savedPlayer = $game->players->firstWhere('id', $game->doctor_save_id); @endphp
                <p style="color:var(--green); font-weight:700;">✅ You are protecting <strong>{{ $savedPlayer?->name }}</strong> tonight.</p>
            @else
                <p style="color:var(--muted); font-size:.9rem; font-weight:600; margin-bottom:.75rem;">Choose a player to protect from the werewolves:</p>
                <div class="vote-grid">
                    @foreach($game->players->where('is_alive',true) as $target)
                        <form method="POST" action="{{ route('games.doctor-save', $game) }}">
                            @csrf
                            <input type="hidden" name="token"     value="{{ $player->token }}">
                            <input type="hidden" name="target_id" value="{{ $target->id }}">
                            <button type="submit" class="vote-btn">{{ $target->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($player->role === 'Seer' && $game->phase === 'night')
        <div class="action-section">
            <h2>🔮 Peek at someone's role</h2>
            @if($player->has_peeked)
                <p style="color:var(--green); font-weight:700;">✅ You've already used your vision this round.</p>
            @else
                <p style="color:var(--muted); font-size:.9rem; font-weight:600; margin-bottom:.75rem;">Choose a player to reveal their true role:</p>
                <div class="vote-grid">
                    @foreach($game->players->where('is_alive',true)->where('id','!=',$player->id) as $target)
                        <form method="POST" action="{{ route('games.seer-peek', $game) }}">
                            @csrf
                            <input type="hidden" name="token"     value="{{ $player->token }}">
                            <input type="hidden" name="target_id" value="{{ $target->id }}">
                            <button type="submit" class="vote-btn">{{ $target->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($game->phase === 'day')
        <div class="action-section">
            <h2>☀️ Vote to eliminate a suspect</h2>
            @if($player->day_vote_target_id)
                @php $dayTarget = $game->players->firstWhere('id', $player->day_vote_target_id); @endphp
                <p style="color:var(--green); font-weight:700;">✅ You voted for <strong>{{ $dayTarget?->name }}</strong>. Waiting for others…</p>
            @else
                <p style="color:var(--muted); font-size:.9rem; font-weight:600; margin-bottom:.75rem;">Who do you think is a werewolf?</p>
                <div class="vote-grid">
                    @foreach($game->players->where('is_alive',true)->where('id','!=',$player->id) as $target)
                        <form method="POST" action="{{ route('games.day-vote', $game) }}">
                            @csrf
                            <input type="hidden" name="token"     value="{{ $player->token }}">
                            <input type="hidden" name="target_id" value="{{ $target->id }}">
                            <button type="submit" class="vote-btn">{{ $target->name }}</button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($player->role === 'Villager' && $game->phase === 'night')
        <div class="action-section" style="text-align:center; padding:2rem;">
            <div style="font-size:2.5rem; margin-bottom:.5rem;">😴</div>
            <p style="color:var(--muted); font-weight:600;">It's night. The village sleeps.<br>Wait for morning to vote.</p>
        </div>
    @endif

@elseif($game->status === 'finished')
    @php $winner = $game->checkWinner() ?? 'unknown'; @endphp
    <div class="action-section" style="text-align:center; padding:2.5rem;">
        @if($winner === 'villagers')
            <div style="font-size:2.5rem; margin-bottom:.5rem;">🎉</div>
            <h2 style="color:var(--green);">Villagers win!</h2>
            <p style="color:var(--muted); font-weight:600;">The werewolves have been defeated.</p>
        @else
            <div style="font-size:2.5rem; margin-bottom:.5rem;">🐺</div>
            <h2 style="color:var(--red);">Werewolves win!</h2>
            <p style="color:var(--muted); font-weight:600;">The village has fallen.</p>
        @endif
    </div>
@else
    <div class="action-section" style="text-align:center; padding:2rem;">
        <p style="color:var(--muted); font-weight:600;">Waiting for the game master to start the game…</p>
    </div>
@endif

<a href="{{ route('games.play', $game) }}" class="nav-link">📋 View game board →</a>
@endsection
