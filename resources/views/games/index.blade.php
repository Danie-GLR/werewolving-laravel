@extends('layouts.app')

@section('title', 'Games')

@section('content')
<style>
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-title {
        font-size: 1.8rem;
        font-weight: 900;
        letter-spacing: -.5px;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-icon {
        font-size: 3.5rem;
        margin-bottom: 1rem;
        display: block;
    }

    .empty-state h2 {
        font-size: 1.3rem;
        font-weight: 800;
        margin-bottom: .5rem;
    }

    .empty-state p {
        color: var(--muted);
        margin-bottom: 1.5rem;
    }

    .game-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: .9rem 1.2rem;
        border-bottom: 1px solid var(--border);
        transition: background .15s;
    }

    .game-row:last-child { border-bottom: none; }
    .game-row:hover { background: rgba(255,255,255,0.03); }

    .game-row-name {
        flex: 1;
        font-weight: 800;
        font-size: 1rem;
    }

    .game-row-meta {
        color: var(--muted);
        font-size: .82rem;
        font-weight: 600;
    }

    .games-table-card {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: 1rem;
    }
</style>

<div class="page-header">
    <div class="page-title">🎮 All Games</div>
    <a href="{{ route('games.create') }}" class="btn">+ New Game</a>
</div>

@if($games->isEmpty())
    <div class="card empty-state">
        <span class="empty-icon">🌙</span>
        <h2>No games yet</h2>
        <p>Create the first one and let the hunt begin.</p>
        <a href="{{ route('games.create') }}" class="btn">Create a Game</a>
    </div>
@else
    <div class="games-table-card">
        @foreach($games as $game)
            <div class="game-row">
                <div class="game-row-name">{{ $game->name }}</div>
                <div class="game-row-meta">{{ $game->players_count }} players</div>
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
                <div class="game-row-meta">{{ $game->created_at->diffForHumans() }}</div>
                <a href="{{ route('games.show', $game) }}" class="btn-outline" style="padding:.35rem .9rem; font-size:.8rem;">View →</a>
            </div>
        @endforeach
    </div>
@endif
@endsection
