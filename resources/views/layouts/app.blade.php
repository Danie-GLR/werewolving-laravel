<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🐺 Werewolf — @yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #3a3a3a;
            --surface:   #484848;
            --surface2:  #525252;
            --pink:      #e8366a;
            --pink-dark: #c42d59;
            --pink-light:#ff6b95;
            --yellow:    #f5c842;
            --green:     #6abf4b;
            --red:       #e05a2b;
            --wolf-red:  #d63031;
            --text:      #ffffff;
            --muted:     #b0b0b0;
            --radius:    16px;
            --radius-sm: 10px;
            --shadow:    0 4px 16px rgba(0,0,0,.4);
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Nav ── */
        nav {
            background: var(--surface);
            padding: .9rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.3);
        }
        .brand {
            font-family: 'Fredoka One', cursive;
            font-size: 1.5rem;
            color: var(--pink);
            margin-right: auto;
            letter-spacing: .03em;
            text-shadow: 0 2px 4px rgba(0,0,0,.3);
        }
        nav a {
            color: var(--muted);
            text-decoration: none;
            font-weight: 700;
            font-size: .95rem;
            padding: .35rem .8rem;
            border-radius: 20px;
            transition: all .15s;
        }
        nav a:hover, nav a.active {
            color: #fff;
            background: rgba(232,54,106,.2);
        }

        /* ── Layout ── */
        main {
            padding: 1.5rem 1rem;
            max-width: 860px;
            margin: 0 auto;
        }

        /* ── Flash ── */
        .flash {
            background: linear-gradient(135deg, #2d6a4f, #1b4332);
            border: 1px solid #40916c;
            border-radius: var(--radius-sm);
            padding: .75rem 1rem;
            margin-bottom: 1.2rem;
            font-weight: 700;
            font-size: .95rem;
        }
        .flash-error {
            background: linear-gradient(135deg, #6b1e1e, #3d0e0e);
            border-color: #c0392b;
        }

        /* ── Cards ── */
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
        }

        /* ── Buttons ── */
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--pink), var(--pink-dark));
            color: #fff;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 1rem;
            padding: .6rem 1.6rem;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(232,54,106,.4);
            transition: transform .1s, box-shadow .1s;
            letter-spacing: .02em;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(232,54,106,.5);
        }
        .btn:active { transform: translateY(0); }

        .btn-ghost {
            background: transparent;
            border: 2px solid var(--pink);
            color: var(--pink);
            box-shadow: none;
        }
        .btn-ghost:hover { background: rgba(232,54,106,.15); box-shadow: none; }

        .btn-green-fill {
            background: linear-gradient(135deg, #6abf4b, #4e9e32);
            box-shadow: 0 4px 12px rgba(106,191,75,.35);
        }
        .btn-green-fill:hover { box-shadow: 0 6px 16px rgba(106,191,75,.5); }

        .btn-yellow-fill {
            background: linear-gradient(135deg, #f5c842, #e0a800);
            color: #333;
            box-shadow: 0 4px 12px rgba(245,200,66,.35);
        }

        .btn-sm { font-size: .82rem; padding: .4rem 1rem; }

        /* ── Inputs ── */
        input[type="text"], textarea, select {
            width: 100%;
            background: var(--bg);
            border: 2px solid var(--surface2);
            color: var(--text);
            padding: .65rem .9rem;
            border-radius: var(--radius-sm);
            font-family: 'Nunito', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            transition: border-color .15s;
        }
        input[type="text"]:focus, textarea:focus { outline: none; border-color: var(--pink); }

        /* ── Role chips ── */
        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-weight: 800;
            font-size: .78rem;
            padding: .25rem .7rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .rp-Werewolf { background: #c0392b; color: #fff; }
        .rp-Villager { background: #6abf4b; color: #fff; }
        .rp-Seer     { background: #8e44ad; color: #fff; }
        .rp-Doctor   { background: #2980b9; color: #fff; }
        .rp-unknown  { background: var(--surface2); color: var(--muted); }

        /* ── Status badges ── */
        .badge { display: inline-block; padding: .2rem .7rem; border-radius: 20px; font-size: .75rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .badge-waiting  { background: #555; color: #ccc; }
        .badge-assigned { background: #4a3080; color: #c4a8ff; }
        .badge-progress { background: #7a4500; color: #ffd97d; }
        .badge-finished { background: #1b4d2e; color: #6ee7a0; }

        /* ── Tables ── */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .65rem .9rem; text-align: left; }
        th { font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); border-bottom: 2px solid var(--surface2); font-weight: 800; }
        tr:not(:last-child) td { border-bottom: 1px solid var(--surface2); }

        /* ── Utilities ── */
        .text-muted { color: var(--muted); }
        .text-pink   { color: var(--pink); }
        .flex { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; }
        h1 { font-family: 'Fredoka One', cursive; font-size: 1.9rem; letter-spacing: .02em; }
        h2 { font-family: 'Fredoka One', cursive; font-size: 1.3rem; letter-spacing: .02em; }
    </style>
</head>
<body>
    <nav>
        <span class="brand">🐺 Werewolf</span>
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
        <a href="{{ route('games.index') }}" class="{{ request()->routeIs('games.*') ? 'active' : '' }}">Games</a>
    </nav>
    <main>
        @if(session('success'))
            <div class="flash">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="flash flash-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
