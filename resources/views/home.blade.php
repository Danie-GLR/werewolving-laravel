@extends('layouts.app')

@section('title', 'Home')

@section('content')
<style>
    .home-hero {
        text-align: center;
        padding: 4rem 1rem 3rem;
    }

    .hero-wolf {
        width: 130px;
        height: 130px;
        object-fit: contain;
        filter: drop-shadow(0 8px 30px rgba(255,61,127,0.4));
        margin-bottom: 1.5rem;
        animation: floatWolf 3s ease-in-out infinite;
    }

    @keyframes floatWolf {
        0%, 100% { transform: translateY(0); }
        50%       { transform: translateY(-10px); }
    }

    .hero-title {
        font-size: clamp(2rem, 6vw, 3.2rem);
        font-weight: 900;
        letter-spacing: -1px;
        line-height: 1.1;
        margin-bottom: .75rem;
    }

    .hero-title span { color: var(--pink); }

    .hero-sub {
        color: var(--muted);
        font-size: 1.1rem;
        margin-bottom: 2.5rem;
        font-weight: 600;
    }

    .roles-row {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        flex-wrap: wrap;
        margin-top: 3rem;
    }

    .role-pill {
        background: var(--bg2);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.2rem 1.5rem;
        text-align: center;
        min-width: 110px;
        transition: transform .2s, border-color .2s;
    }

    .role-pill:hover {
        transform: translateY(-4px);
        border-color: rgba(255,61,127,0.3);
    }

    .role-pill img {
        width: 64px;
        height: 64px;
        object-fit: contain;
        filter: drop-shadow(0 4px 10px rgba(0,0,0,0.4));
        margin-bottom: .5rem;
    }

    .role-pill-name {
        font-size: .8rem;
        font-weight: 800;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .how-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .how-step {
        background: var(--bg3);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.2rem;
    }

    .how-num {
        font-size: 1.8rem;
        font-weight: 900;
        color: var(--pink);
        line-height: 1;
        margin-bottom: .4rem;
    }

    .how-label {
        font-weight: 800;
        font-size: .95rem;
        margin-bottom: .3rem;
    }

    .how-desc {
        font-size: .82rem;
        color: var(--muted);
        line-height: 1.5;
    }

    h2.section-title {
        font-size: 1.1rem;
        font-weight: 900;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 1rem;
    }
</style>

<div class="home-hero">
    <img src="{{ asset('images/icon_werewolf.png') }}" alt="Werewolf" class="hero-wolf">
    <div class="hero-title">Who's the <span>Werewolf</span><br>among you?</div>
    <p class="hero-sub">Trust no one. Survive the night.</p>
    <a href="{{ route('games.index') }}" class="btn" style="font-size:1.1rem; padding:.7rem 2rem;">Play Now →</a>

    <div class="roles-row">
        <div class="role-pill">
            <img src="{{ asset('images/icon_werewolf.png') }}" alt="Werewolf">
            <div class="role-pill-name">Werewolf</div>
        </div>
        <div class="role-pill">
            <img src="{{ asset('images/icon_villager.png') }}" alt="Villager">
            <div class="role-pill-name">Villager</div>
        </div>
        <div class="role-pill">
            <img src="{{ asset('images/icon_seer.png') }}" alt="Seer">
            <div class="role-pill-name">Seer</div>
        </div>
        <div class="role-pill">
            <img src="{{ asset('images/icon_doctor.png') }}" alt="Doctor">
            <div class="role-pill-name">Doctor</div>
        </div>
    </div>
</div>

<div style="margin-top:2rem;">
    <h2 class="section-title">How it works</h2>
    <div class="how-grid">
        <div class="how-step">
            <div class="how-num">01</div>
            <div class="how-label">Create a game</div>
            <div class="how-desc">One person hosts and becomes the Game Master.</div>
        </div>
        <div class="how-step">
            <div class="how-num">02</div>
            <div class="how-label">Everyone joins</div>
            <div class="how-desc">Share the lobby URL — each player joins on their own device.</div>
        </div>
        <div class="how-step">
            <div class="how-num">03</div>
            <div class="how-label">Roles are assigned</div>
            <div class="how-desc">Everyone secretly sees only their own role.</div>
        </div>
        <div class="how-step">
            <div class="how-num">04</div>
            <div class="how-label">Hunt or survive</div>
            <div class="how-desc">Vote, bluff, and eliminate — may the best team win.</div>
        </div>
    </div>
</div>
@endsection
