<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Werewolf  @yield('title')</title>
    <style>
        body { font-family: sans-serif; background: #1a1a2e; color: #eee; margin: 0; }
        nav { background: #16213e; padding: 1rem 2rem; display: flex; gap: 2rem; align-items: center; }
        nav a { color: #e94560; text-decoration: none; font-weight: bold; }
        nav a:hover, nav a.active { color: #fff; }
        nav .brand { font-size: 1.3rem; margin-right: auto; }
        main { padding: 2rem; max-width: 900px; margin: 0 auto; }
        .card { background: #16213e; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
        .btn { background: #e94560; color: white; padding: .5rem 1.2rem; border: none;
               border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 1rem; }
        .btn:hover { background: #c73652; }
    </style>
</head>
<body>
    <nav>
        <span class="brand">🐺 Werewolf</span>
        <a href="{{ route('home') }}"
           class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
        <a href="{{ route('games.index') }}"
           class="{{ request()->routeIs('games.*') ? 'active' : '' }}">Game</a>
    </nav>
    <main>
        @if(session('success'))
            <div style="background:#2d6a4f;padding:.8rem 1rem;border-radius:4px;margin-bottom:1rem;">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>