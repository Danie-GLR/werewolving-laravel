<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found · Werewolving</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --pink:   #FF3D7F;
            --bg:     #1a1025;
            --bg2:    #231535;
            --muted:  #9b87b8;
            --text:   #f0e6ff;
            --border: rgba(255,255,255,0.08);
            --radius: 14px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            background-image:
                radial-gradient(ellipse at 20% 10%, rgba(255,61,127,0.07) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 90%, rgba(120,60,200,0.07) 0%, transparent 60%);
        }

        .wolf-img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            filter: drop-shadow(0 8px 24px rgba(255,61,127,0.3));
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
            opacity: .5;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-10px); }
        }

        .error-code {
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            color: var(--pink);
            letter-spacing: -4px;
            margin-bottom: .5rem;
            opacity: .8;
        }

        h1 {
            font-size: 1.6rem;
            font-weight: 900;
            margin-bottom: .75rem;
            letter-spacing: -.5px;
        }

        p {
            color: var(--muted);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 2rem;
            max-width: 380px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            background: var(--pink);
            color: #fff;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .95rem;
            padding: .6rem 1.6rem;
            border-radius: 30px;
            text-decoration: none;
            transition: background .2s, transform .1s;
            box-shadow: 0 4px 15px rgba(255,61,127,0.25);
        }

        .btn:hover { background: #D42E6A; transform: translateY(-1px); }

        .divider {
            width: 40px;
            height: 2px;
            background: rgba(255,61,127,0.3);
            margin: 1.5rem auto;
            border-radius: 2px;
        }

        .home-link {
            color: var(--muted);
            font-size: .85rem;
            font-weight: 700;
            text-decoration: none;
            margin-top: 1rem;
            display: block;
            transition: color .2s;
        }

        .home-link:hover { color: var(--text); }
    </style>
</head>
<body>
    <img src="/images/icon_werewolf.png" alt="Wolf" class="wolf-img">
    <div class="error-code">404</div>
    <h1>Lost in the night…</h1>
    <p>The page you're looking for doesn't exist, was moved, or the werewolves got to it first.</p>
    <a href="/" class="btn">Back to safety →</a>
    <div class="divider"></div>
    <a href="/games" class="home-link">View all games</a>
</body>
</html>
