@extends('layouts.app')

@section('title', 'Your Role — ' . $game->name)

@section('content')
<style>
    .role-card {
        text-align: center;
        padding: 2.5rem 2rem;
        border-radius: 12px;
        max-width: 480px;
        margin: 0 auto 1.5rem;
    }
    .role-icon-big { font-size: 5rem; margin-bottom: .75rem; }
    .role-title    { font-size: 2rem; font-weight: bold; margin-bottom: .5rem; }
    .role-desc     { color: #9ca3af; font-size: 1rem; line-height: 1.6; margin-bottom: 1.5rem; }

    .role-Werewolf { background: #2d0d0d; border: 2px solid #e94560; }
    .role-Villager { background: #0d2d18; border: 2px solid #22c55e; }
    .role-Seer     { background: #1a0d2d; border: 2px solid #a855f7; }
    .role-Doctor   { background: #0d1a2d; border: 2px solid #3b82f6; }

    .action-section { background: #16213e; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
    .action-section h2 { margin-top: 0; margin-bottom: 1rem; }
    .player-vote-list { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: .75rem; }
    .vote-btn {
        background: #0f3460; border: 1px solid #e94560; color: #eee;
        padding: .5rem 1rem; border-radius: 6px; cursor: pointer;
        font-size: .95rem; transition: background .15s;
    }
    .vote-btn:hover { background: #e94560; }
    .dead-tag { color: #6b7280; font-size: .8rem; }
    .phase-badge {
        display: inline-block; padding: .3rem .9rem; border-radius: 20px;
        font-size: .85rem; font-weight: bold; margin-bottom: 1rem;
    }
    .phase-night { background: #1e1b4b; color: #a5b4fc; border: 1px solid #4f46e5; }
    .phase-day   { background: #1c1a07; color: #fde68a; border: 1px solid #d97706; }
    .alert-peek  { background: #1a0d2d; border: 1px solid #a855f7; border-radius: 6px;
                   padding: .8rem 1rem; color: #e9d5ff; margin-bottom: 1rem; }
    .btn-outline {
        background: transparent; border: 1px solid #e94560; color: #e94560;
        padding: .5rem 1.2rem; border-radius: 4px; cursor: pointer;
        text-decoration: none; font-size: .95rem; display: inline-block;
    }
    .btn-outline:hover { background: #e94560; color: #fff; }
</style>

{{-- Flash messages --}}
@if(session('peek_result'))
    <div class="alert-peek">{{ session('peek_result') }}</div>
@endif

{{-- Game status --}}
<div style="text-align:center; margin-bottom:1.5rem;">
    @if($game->status === 'in_progress')
        <span class="phase-badge {{ $game->phase === 'night' ? 'phase-night' : 'phase-day' }}">
            {{ $game->phase === 'night' ? '🌙 Night — Round ' . $game->round : '☀️ Day — Round ' . $game->round }}
        </span>
    @elseif($game->status === 'finished')
        <span class="phase-badge" style="background:#1a2d0d; color:#86efac; border:1px solid #22c55e;">✅ Game Over</span>
    @else
        <span class="phase-badge" style="background:#1a1a2e; color:#9ca3af; border:1px solid #374151;">⏳ Not started yet</span>
    @endif
</div>

{{-- Role card --}}
<div class="role-card role-{{ $player->role ?? 'Villager' }}">
    <div class="role-icon-big">{{ $player->role_icon }}</div>
    <div class="role-title">{{ $player->name }}, you are a {{ $player->role ?? '?' }}</div>

    <div class="role-desc">
        @switch($player->role)
            @case('Werewolf')
                You wake each night to eliminate a villager. Work with your fellow wolves.
                Win when werewolves equal or outnumber the village.
                @break
            @case('Villager')
                You have no special power, only your wits. Vote out the wolves during the day.
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
        <div style="color:#e94560; font-weight:bold;">☠️ You have been eliminated</div>
    @endif
</div>

{{-- ── Actions based on role + phase ────────────────────────────────────────── --}}

@if($game->status === 'in_progress' && $player->is_alive)

    {{-- WEREWOLF — Night vote --}}
    @if($player->role === 'Werewolf' && $game->phase === 'night')
        <div class="action-section">
            <h2>🐺 Choose your victim tonight</h2>

            @if($player->night_vote_target_id)
                @php $alreadyTarget = $game->players->firstWhere('id', $player->night_vote_target_id); @endphp
                <p style="color:#86efac;">✅ You voted for <strong>{{ $alreadyTarget?->name }}</strong>. Waiting for other wolves…</p>
            @else
                {{-- Show fellow wolves --}}
                @php $wolves = $game->players->where('role','Werewolf')->where('is_alive',true)->where('id','!=',$player->id); @endphp
                @if($wolves->count())
                    <p style="color:#fca5a5; font-size:.85rem; margin-bottom:.75rem;">
                        Fellow wolves: {{ $wolves->pluck('name')->join(', ') }}
                    </p>
                @endif

                <p style="color:#9ca3af; font-size:.9rem;">Pick a non-wolf player to eliminate:</p>
                <div class="player-vote-list">
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

    {{-- DOCTOR — Save --}}
    @if($player->role === 'Doctor' && $game->phase === 'night')
        <div class="action-section">
            <h2>💊 Protect someone tonight</h2>

            @if($game->doctor_save_id)
                @php $savedPlayer = $game->players->firstWhere('id', $game->doctor_save_id); @endphp
                <p style="color:#86efac;">✅ You are protecting <strong>{{ $savedPlayer?->name }}</strong> tonight.</p>
            @else
                <p style="color:#9ca3af; font-size:.9rem;">Choose a player to protect from the werewolves:</p>
                <div class="player-vote-list">
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

    {{-- SEER — Peek --}}
    @if($player->role === 'Seer' && $game->phase === 'night')
        <div class="action-section">
            <h2>🔮 Peek at someone's role</h2>

            @if($player->has_peeked)
                <p style="color:#86efac;">✅ You've already used your vision this round. Rest now.</p>
            @else
                <p style="color:#9ca3af; font-size:.9rem;">Choose a player to reveal their true role:</p>
                <div class="player-vote-list">
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

    {{-- ALL ALIVE — Day vote --}}
    @if($game->phase === 'day')
        <div class="action-section">
            <h2>☀️ Vote to eliminate a suspect</h2>

            @if($player->day_vote_target_id)
                @php $dayTarget = $game->players->firstWhere('id', $player->day_vote_target_id); @endphp
                <p style="color:#86efac;">✅ You voted for <strong>{{ $dayTarget?->name }}</strong>. Waiting for others…</p>
            @else
                <p style="color:#9ca3af; font-size:.9rem;">Who do you think is a werewolf?</p>
                <div class="player-vote-list">
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

    {{-- Villager waiting during night --}}
    @if($player->role === 'Villager' && $game->phase === 'night')
        <div class="action-section" style="text-align:center; padding:2rem;">
            <div style="font-size:2.5rem;">😴</div>
            <p style="color:#9ca3af; margin-top:.75rem;">It's night. The village sleeps.<br>Wait for morning to vote.</p>
        </div>
    @endif

@elseif($game->status === 'finished')
    @php $winner = $game->checkWinner() ?? 'unknown'; @endphp
    <div class="action-section" style="text-align:center; padding:2rem;">
        @if($winner === 'villagers')
            <div style="font-size:2.5rem;">🎉</div>
            <h2>Villagers win!</h2>
            <p style="color:#86efac;">The werewolves have been defeated.</p>
        @else
            <div style="font-size:2.5rem;">🐺</div>
            <h2>Werewolves win!</h2>
            <p style="color:#fca5a5;">The village has fallen.</p>
        @endif
    </div>

@else
    <div class="action-section" style="text-align:center; padding:2rem;">
        <p style="color:#9ca3af;">Waiting for the game master to start the game…</p>
    </div>
@endif

{{-- ── Navigation ─────────────────────────────────────────────────────────── --}}
<div style="text-align:center; margin-top:1rem;">
    <a href="{{ route('games.play', $game) }}" class="btn-outline">📋 View game board</a>
</div>
@endsection
