@extends('layouts.app')
@section('title', 'Home')
@section('content')
<style>
    .hero {
        text-align: center;
        padding: 3rem 1rem 2.5rem;
    }
    .hero-title {
        font-family: 'Fredoka One', cursive;
        font-size: 3.5rem;
        color: var(--pink);
        text-shadow: 0 4px 16px rgba(232,54,106,.4);
        margin-bottom: .5rem;
        line-height: 1.1;
    }
    .hero-sub {
        color: var(--muted);
        font-size: 1.15rem;
        font-weight: 600;
        margin-bottom: 2rem;
    }
    .role-showcase {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin: 2rem 0;
    }
    .role-showcase-pill {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .7rem 1.4rem;
        border-radius: 50px;
        font-family: 'Fredoka One', cursive;
        font-size: 1.15rem;
        box-shadow: 0 4px 14px rgba(0,0,0,.35);
        min-width: 160px;
    }
    .role-showcase-pill .icon { font-size: 2rem; }
    .pill-wolf    { background: #c0392b; color: #fff; }
    .pill-village { background: #6abf4b; color: #fff; }
    .pill-seer    { background: #8e44ad; color: #fff; }
    .pill-doctor  { background: #2980b9; color: #fff; }
</style>

<div class="hero">
    <div class="hero-title">Werewolves<br>Amongst Us</div>
    <div class="hero-sub">whom to trust, only time can tell</div>

    <div class="role-showcase">
        <div class="role-showcase-pill pill-wolf">   <span class="icon">🐺</span> Werewolf </div>
        <div class="role-showcase-pill pill-village"><span class="icon">👨‍🌾</span> Villager </div>
        <div class="role-showcase-pill pill-seer">  <span class="icon">🔮</span> Seer     </div>
        <div class="role-showcase-pill pill-doctor"><span class="icon">💊</span> Doctor   </div>
    </div>

    <a href="{{ route('games.index') }}" class="btn" style="font-size:1.2rem; padding:.75rem 2.5rem;">
        Play Now 🎮
    </a>
</div>
@endsection
