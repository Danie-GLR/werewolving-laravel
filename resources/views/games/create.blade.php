@extends('layouts.app')

@section('title', 'Create Game')

@section('content')
<style>
    .form-group{margin-bottom:1.2rem;}
    .form-group label{display:block;margin-bottom:.4rem;font-size:.85rem;color:#9ca3af;}
    .form-group input{width:100%;box-sizing:border-box;background:#0f172a;border:1px solid #334155;
                       color:#eee;padding:.6rem .8rem;border-radius:6px;font-size:1rem;}
    .form-group input:focus{outline:none;border-color:#e94560;}
    .form-error{color:#fca5a5;font-size:.8rem;margin-top:.3rem;}
    .info-box{background:#0f0f1a;border:1px solid #2a2a40;border-radius:6px;
               padding:.8rem 1rem;margin-bottom:1.2rem;font-size:.82rem;color:#9ca3af;}
    .info-box strong{color:#c084fc;}
</style>

<div style="max-width:480px;margin:0 auto;">
    <a href="{{ route('games.index') }}" style="color:#9ca3af;display:inline-block;margin-bottom:1rem;">← Back</a>
    <h1 style="margin-bottom:.25rem;">Create a Game</h1>
    <p style="color:#9ca3af;margin-bottom:1.5rem;">Give the game a name. Players will join from their own devices.</p>

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
                <strong>How it works:</strong><br>
                1. You create the game — you become the Game Master.<br>
                2. Share the lobby URL with players — each joins on their own device and picks their name.<br>
                3. You assign roles and start — everyone secretly sees only their own role.<br>
                4. Night/day voting happens per device. Nobody else can see your role.
            </div>

            <button type="submit" class="btn" style="width:100%;">Create Game →</button>
        </form>
    </div>
</div>
@endsection
