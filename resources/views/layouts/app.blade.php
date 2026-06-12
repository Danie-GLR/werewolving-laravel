<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werewolving — @yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --pink:      #FF3D7F;
            --pink-dark: #D42E6A;
            --pink-glow: rgba(255,61,127,0.25);
            --bg:        #1a1025;
            --bg2:       #231535;
            --bg3:       #2d1f42;
            --border:    rgba(255,255,255,0.08);
            --text:      #f0e6ff;
            --muted:     #9b87b8;
            --green:     #2dce89;
            --yellow:    #ffd166;
            --red:       #e94560;
            --radius:    14px;
            --radius-sm: 8px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            background-image:
                radial-gradient(ellipse at 20% 10%, rgba(255,61,127,0.07) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 90%, rgba(120,60,200,0.07) 0%, transparent 60%);
        }

        /* ── Nav ── */
        nav {
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            padding: .9rem 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-right: auto;
            text-decoration: none;
        }

        .nav-brand img {
            width: 36px;
            height: 36px;
            object-fit: contain;
        }

        .nav-brand-text {
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--text);
            letter-spacing: -.5px;
        }

        .nav-brand-text span { color: var(--pink); }

        nav a:not(.nav-brand) {
            color: var(--muted);
            text-decoration: none;
            font-weight: 700;
            font-size: .95rem;
            padding: .35rem .75rem;
            border-radius: 20px;
            transition: all .2s;
        }

        nav a:not(.nav-brand):hover,
        nav a:not(.nav-brand).active {
            color: var(--text);
            background: rgba(255,61,127,0.15);
        }

        /* ── Main ── */
        main {
            padding: 2rem 1.5rem;
            max-width: 920px;
            margin: 0 auto;
        }

        /* ── Cards ── */
        .card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-block;
            background: var(--pink);
            color: #fff;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .95rem;
            padding: .55rem 1.4rem;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            letter-spacing: .3px;
            transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 4px 15px var(--pink-glow);
        }

        .btn:hover {
            background: var(--pink-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px var(--pink-glow);
        }

        .btn:active { transform: translateY(0); }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--pink);
            color: var(--pink);
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .95rem;
            padding: .5rem 1.3rem;
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-outline:hover { background: var(--pink); color: #fff; }

        .btn-green {
            background: var(--green);
            color: #fff;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .95rem;
            padding: .55rem 1.4rem;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s, transform .1s;
        }

        .btn-green:hover { background: #26b577; transform: translateY(-1px); }

        .btn-yellow {
            background: var(--yellow);
            color: #1a1025;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .95rem;
            padding: .55rem 1.4rem;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            transition: background .2s, transform .1s;
        }

        .btn-yellow:hover { background: #f0c040; transform: translateY(-1px); }

        /* ── Badges ── */
        .badge {
            display: inline-block;
            padding: .2rem .8rem;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .3px;
            text-transform: uppercase;
        }

        .badge-waiting  { background: rgba(155,135,184,0.15); color: var(--muted); border: 1px solid rgba(155,135,184,0.2); }
        .badge-assigned { background: rgba(168,85,247,0.15);  color: #c084fc;      border: 1px solid rgba(168,85,247,0.25); }
        .badge-progress { background: rgba(255,209,102,0.15); color: var(--yellow);border: 1px solid rgba(255,209,102,0.25); }
        .badge-finished { background: rgba(45,206,137,0.15);  color: var(--green); border: 1px solid rgba(45,206,137,0.25); }

        /* ── Tables ── */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .7rem 1rem; text-align: left; }
        th { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--border); }
        tr:not(:last-child) td { border-bottom: 1px solid var(--border); }

        /* ── Flash ── */
        .flash-success {
            background: rgba(45,206,137,0.12);
            border: 1px solid rgba(45,206,137,0.3);
            color: var(--green);
            padding: .8rem 1.2rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            font-weight: 700;
        }

        /* ── Utilities ── */
        .flex { display: flex; gap: .75rem; align-items: center; }
        .text-muted { color: var(--muted); }
        .text-center { text-align: center; }

        /* ── Role icon images ── */
        .role-img {
            width: 72px;
            height: 72px;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4));
        }

        .role-img-lg {
            width: 110px;
            height: 110px;
            object-fit: contain;
            filter: drop-shadow(0 6px 20px rgba(0,0,0,0.5));
        }
    </style>
</head>
<body>
    <nav>
        <a href="{{ route('home') }}" class="nav-brand">
            <img src="{{ asset('images/icon_werewolf.png') }}" alt="wolf">
            <span class="nav-brand-text">Were<span>wolving</span></span>
        </a>
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
        <a href="{{ route('games.index') }}" class="{{ request()->routeIs('games.*') ? 'active' : '' }}">Games</a>
    </nav>

    <main>
        @if(session('success'))
            <div class="flash-success">✓ {{ session('success') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
