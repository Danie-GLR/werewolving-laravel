@extends('layouts.app')
@section('title', 'Games')
@section('content')
<style>
    .page-header { display:flex; align-items:center; margin-bottom:1.5rem; gap:1rem; flex-wrap:wrap; }
    .page-header h1 { flex:1; }
    .game-row { display:flex; align-items:center; gap:1rem; padding:.9rem 1.2rem;
                background:var(--surface2); border-radius:var(--radius-sm); margin-bottom:.6rem;
                box-shadow:0 2px 8px rgba(0,0,0,.2); transition:background .15s; }
    .game-row:hover { background:#5a5a5a; }
    .game-name { font-weight:800; font-size:1rem; flex:1; }
    .player-count { color:var(--muted); font-size:.85rem; font-weight:700; min-width:60px; }
    .created-at { color:var(--muted); font-size:.8rem; min-width:90px; text-align:right; }
    .empty-hero { text-align:center; padding:3rem 1rem; }
    .empty-icon { font-size:4rem; margin-bottom:1rem; }
</style>

<div class="page-header">
    <h1>🎮 All Games</h1>
    <a href="{{ route('games.create') }}" class="btn">+ New Game</a>
</div>

@if($games->isEmpty())
    <div class="card empty-hero">
        <div class="empty-icon">🌙</div>
        <p style="color:var(--muted);font-weight:700;margin-bottom:1.5rem;">No games yet. Start the hunt!</p>
        <a href="{{ route('games.create') }}" class="btn">Create a Game</a>
    </div>
@else
    @foreach($games as $game)
        @php
            $bc = match($game->status) {
                'waiting'        => 'badge-waiting',
                'roles_assigned' => 'badge-assigned',
                'in_progress'    => 'badge-progress',
                'finished'       => 'badge-finished',
                default          => 'badge-waiting',
            };
        @endphp
        <div class="game-row">
            <div class="game-name">{{ $game->name }}</div>
            <div class="player-count">👤 {{ $game->players_count }}</div>
            <span class="badge {{ $bc }}">{{ $game->status_label }}</span>
            <div class="created-at">{{ $game->created_at->diffForHumans() }}</div>
            <a href="{{ route('games.lobby', $game) }}" class="btn btn-sm">View →</a>
        </div>
    @endforeach
@endif
@endsection
