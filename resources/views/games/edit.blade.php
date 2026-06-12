@extends('layouts.app')

@section('title', 'Edit — ' . $game->name)

@section('content')
<style>
    .form-wrap { max-width: 480px; margin: 0 auto; }
    .back-link {
        color: var(--muted); text-decoration: none; font-weight: 700;
        font-size: .9rem; display: inline-flex; align-items: center;
        gap: .3rem; margin-bottom: 1.5rem; transition: color .2s;
    }
    .back-link:hover { color: var(--text); }
    .form-wrap h1 { font-size: 1.8rem; font-weight: 900; letter-spacing: -.5px; margin-bottom: .3rem; }
    .form-wrap .subtitle { color: var(--muted); font-size: .95rem; margin-bottom: 1.5rem; font-weight: 600; }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label {
        display: block; margin-bottom: .45rem; font-size: .82rem;
        font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .05em;
    }
    .form-group input {
        width: 100%; background: var(--bg); border: 1.5px solid var(--border);
        color: var(--text); padding: .7rem 1rem; border-radius: var(--radius-sm);
        font-size: 1rem; font-family: 'Nunito', sans-serif; font-weight: 600; transition: border-color .2s;
    }
    .form-group input:focus { outline: none; border-color: var(--pink); }
    .form-group input::placeholder { color: var(--muted); }
    .form-error { color: #fca5a5; font-size: .8rem; margin-top: .35rem; font-weight: 700; }
    .btn-danger {
        background: transparent; border: 2px solid var(--red); color: var(--red);
        font-family: 'Nunito', sans-serif; font-weight: 800; font-size: .95rem;
        padding: .5rem 1.3rem; border-radius: 30px; cursor: pointer; transition: all .2s;
    }
    .btn-danger:hover { background: var(--red); color: #fff; }
    .delete-section {
        background: rgba(233,69,96,0.05); border: 1px solid rgba(233,69,96,0.2);
        border-radius: var(--radius); padding: 1.2rem 1.5rem; margin-top: 1.5rem;
    }
    .delete-section h3 { font-size: 1rem; font-weight: 900; margin-bottom: .4rem; color: var(--red); }
    .delete-section p { font-size: .85rem; color: var(--muted); margin-bottom: 1rem; font-weight: 600; }
</style>

<div class="form-wrap">
    <a href="{{ route('games.show', $game) }}" class="back-link">← Back to game</a>

    <h1>Edit Game</h1>
    <p class="subtitle">Change the name of "{{ $game->name }}".</p>

    <div class="card">
        <form method="POST" action="{{ route('games.update', $game) }}">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label for="name">Game Name</label>
                <input type="text" id="name" name="name"
                       value="{{ old('name', $game->name) }}"
                       placeholder="e.g. Night at the Tavern"
                       minlength="2" maxlength="60" required autofocus>
                @error('name')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn" style="width:100%; padding:.7rem;">
                Save Changes →
            </button>
        </form>
    </div>

    {{-- Danger zone --}}
    @if($game->status === 'waiting' || $game->status === 'finished')
    <div class="delete-section">
        <h3>⚠️ Delete Game</h3>
        <p>This permanently deletes the game and all its players. This cannot be undone.</p>
        <form method="POST" action="{{ route('games.destroy', $game) }}"
              onsubmit="return confirm('Are you sure you want to delete \'{{ addslashes($game->name) }}\'? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">Delete Game</button>
        </form>
    </div>
    @else
    <div class="delete-section">
        <h3>⚠️ Delete Game</h3>
        <p>You can only delete a game that is waiting to start or already finished. End the game first.</p>
    </div>
    @endif
</div>
@endsection
