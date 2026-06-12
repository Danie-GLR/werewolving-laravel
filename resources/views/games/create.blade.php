@extends('layouts.app')
@section('title', 'Create Game')
@section('content')
<style>
    .create-wrap { max-width:480px; margin:0 auto; }
    .form-label  { display:block; font-weight:800; font-size:.85rem; margin-bottom:.4rem; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
    .form-group  { margin-bottom:1.2rem; }
    .form-error  { color:#ff6b95; font-size:.82rem; font-weight:700; margin-top:.3rem; }
    .how-it-works { background:var(--bg); border-radius:var(--radius-sm); padding:1rem 1.2rem; margin-bottom:1.2rem; }
    .how-it-works .step { display:flex; gap:.75rem; align-items:flex-start; margin-bottom:.6rem; font-size:.88rem; font-weight:600; color:var(--muted); }
    .how-it-works .step:last-child { margin-bottom:0; }
    .step-num { background:var(--pink); color:#fff; border-radius:50%; width:22px; height:22px; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:800; flex-shrink:0; margin-top:.1rem; }
</style>

<div class="create-wrap">
    <a href="{{ route('games.index') }}" class="text-muted" style="display:inline-block;margin-bottom:1rem;font-weight:700;">← Back</a>
    <h1 style="margin-bottom:1.5rem;">Create a Game</h1>

    <div class="card">
        <form method="POST" action="{{ route('games.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="name">Game Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}"
                       placeholder="e.g. Night at the Tavern" required autofocus>
                @error('name')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="how-it-works">
                <div class="step"><div class="step-num">1</div><span>You create the game — you become the <strong style="color:#fff;">Game Master</strong></span></div>
                <div class="step"><div class="step-num">2</div><span>Share the lobby URL — each player joins on their own device &amp; picks a name</span></div>
                <div class="step"><div class="step-num">3</div><span>You assign roles — everyone secretly sees <strong style="color:#fff;">only their own role</strong></span></div>
                <div class="step"><div class="step-num">4</div><span>Night &amp; day voting happens per device. No peeking!</span></div>
            </div>

            <button type="submit" class="btn" style="width:100%;font-size:1.05rem;">Create Game →</button>
        </form>
    </div>
</div>
@endsection
