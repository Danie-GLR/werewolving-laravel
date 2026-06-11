@extends('layouts.app')

@section('title', 'All Games')

@section('content')
    <div class="flex" style="margin-bottom:1.5rem;">
        <h1 style="flex:1;">🎮 All Games</h1>
        <a href="{{ route('games.create') }}" class="btn">+ New Game</a>
    </div>

    @if($games->isEmpty())
        <div class="card" style="text-align:center; padding:3rem;">
            <div style="font-size:2.5rem; margin-bottom:.75rem;">🌙</div>
            <p>No games yet. Create the first one and let the hunt begin!</p>
            <a href="{{ route('games.create') }}" class="btn" style="margin-top:1rem;">Create a Game</a>
        </div>
    @else
        <div class="card" style="padding:0; overflow:hidden;">
            <table>
                <thead>
                    <tr>
                        <th>Game Name</th>
                        <th>Players</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($games as $game)
                        <tr>
                            <td><strong>{{ $game->name }}</strong></td>
                            <td>{{ $game->players_count }}</td>
                            <td>
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
                            </td>
                            <td class="text-muted">{{ $game->created_at->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('games.show', $game) }}" class="btn btn-outline" style="padding:.3rem .8rem; font-size:.8rem;">View →</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
