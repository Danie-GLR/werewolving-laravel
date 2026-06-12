@extends('layouts.app')

@section('title', 'Create Game')

@section('content')
<style>
    .form-wrap { max-width: 480px; margin: 0 auto; }

    .back-link {
        color: var(--muted);
        text-decoration: none;
        font-weight: 700;
        font-size: .9rem;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        margin-bottom: 1.5rem;
        transition: color .2s;
    }

    .back-link:hover { color: var(--text); }

    .form-wrap h1 {
        font-size: 1.8rem;
        font-weight: 900;
        letter-spacing: -.5px;
        margin-bottom: .3rem;
    }

    .form-wrap .subtitle {
        color: var(--muted);
        font-size: .95rem;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    .form-group { margin-bottom: 1.2rem; }

    .form-group label {
        display: block;
        margin-bottom: .45rem;
        font-size: .82rem;
        font-weight: 800;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .form-group input {
        width: 100%;
        background: var(--bg);
        border: 1.5px solid var(--border);
        color: var(--text);
        padding: .7rem 1rem;
        border-radius: var(--radius-sm);
        font-size: 1rem;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        transition: border-color .2s;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--pink);
    }

    .form-group input::placeholder { color: var(--muted); }

    .form-error {
        color: #fca5a5;
        font-size: .8rem;
        margin-top: .35rem;
        font-weight: 700;
    }

    .info-box {
        background: var(--bg3);
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 1rem 1.2rem;
        margin-bottom: 1.5rem;
        font-size: .85rem;
        color: var(--muted);
        line-height: 1.7;
    }

    .info-box strong { color: #c084fc; }

    .info-step {
        display: flex;
        gap: .6rem;
        align-items: flex-start;
        margin-bottom: .4rem;
    }

    .info-step:last-child { margin-bottom: 0; }

    .info-step-num {
        background: rgba(255,61,127,0.2);
        color: var(--pink);
        font-weight: 900;
        font-size: .75rem;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: .1rem;
    }
</style>

<div class="form-wrap">
    <a href="{{ route('games.index') }}" class="back-link">← Back</a>

    <h1>Create a Game</h1>
    <p class="subtitle">Give the game a name — players will join from their own devices.</p>

    <div class="card">
        <form method="POST" action="{{ route('games.store') }}">
            @csrf
            <div class="form-group">
                <label for="name">Game Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                       placeholder="e.g. Night at the Tavern" required autofocus>
                @error('name')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="info-box">
                <div class="info-step">
                    <div class="info-step-num">1</div>
                    <div>You create the game — you become the <strong>Game Master</strong>.</div>
                </div>
                <div class="info-step">
                    <div class="info-step-num">2</div>
                    <div>Share the lobby URL — each player joins on their own device.</div>
                </div>
                <div class="info-step">
                    <div class="info-step-num">3</div>
                    <div>Assign roles — everyone sees only their secret role.</div>
                </div>
                <div class="info-step">
                    <div class="info-step-num">4</div>
                    <div>Vote, hunt, survive. Nobody sees your role but you.</div>
                </div>
            </div>

            <button type="submit" class="btn" style="width:100%; padding:.75rem; font-size:1rem;">
                Create Game →
            </button>
        </form>
    </div>
</div>
@endsection
