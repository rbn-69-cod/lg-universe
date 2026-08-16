<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#050711">
    <title>LG Universe | Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;800;900&family=Russo+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --cyan: #08e7ff;
            --green: #31f7a4;
            --pink: #ff197b;
            --text: #fff;
            --muted: rgba(255,255,255,.66);
            --line: rgba(255,255,255,.14);
        }

        * { box-sizing: border-box; }

        html, body {
            min-height: 100%;
            margin: 0;
            font-family: Inter, system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 12% 12%, rgba(255,25,123,.32), transparent 34%),
                radial-gradient(circle at 86% 86%, rgba(8,231,255,.22), transparent 36%),
                linear-gradient(135deg, #08000d 0%, #050711 58%, #062230 100%);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: linear-gradient(to bottom, rgba(0,0,0,.95), transparent 90%);
        }

        a, button, input { font: inherit; }

        .page {
            min-height: 100dvh;
            width: min(1120px, calc(100% - 28px));
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(340px, 420px);
            gap: 22px;
            align-items: center;
            padding: 28px 0;
            position: relative;
            z-index: 1;
        }

        .hero {
            min-height: 640px;
            border: 1px solid var(--line);
            border-radius: 34px;
            padding: 34px;
            display: grid;
            align-content: space-between;
            overflow: hidden;
            background:
                linear-gradient(145deg, rgba(126,40,255,.54), rgba(8,231,255,.14) 52%, rgba(255,25,123,.22)),
                rgba(8, 10, 24, .76);
            box-shadow: 0 34px 120px rgba(0,0,0,.48);
            position: relative;
        }

        .hero::after {
            content: "LG";
            position: absolute;
            right: -10px;
            bottom: -34px;
            font-size: 190px;
            line-height: 1;
            font-weight: 900;
            color: rgba(255,255,255,.055);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            text-decoration: none;
            position: relative;
            z-index: 1;
        }

        .mark {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            border: 1px solid var(--line);
            background: rgba(255,255,255,.11);
            font-weight: 900;
            box-shadow: 0 18px 60px rgba(8,231,255,.14);
        }

        .brand b { display: block; font-size: 22px; line-height: 1; }
        .brand span { color: var(--muted); font-size: 13px; font-weight: 800; }

        .hero-copy {
            position: relative;
            z-index: 1;
        }

        .hero-copy h1 {
            max-width: 620px;
            margin: 0;
            font-size: clamp(44px, 7vw, 82px);
            line-height: .88;
            font-weight: 900;
            letter-spacing: 0;
        }

        .hero-copy h1 span { color: var(--cyan); }
        .hero-copy p { max-width: 560px; margin: 18px 0 0; color: var(--muted); line-height: 1.7; font-weight: 700; }

        .metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .metric {
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 18px;
            padding: 16px;
            background: rgba(0,0,0,.22);
        }

        .metric b { display: block; font-size: 30px; line-height: 1; }
        .metric span { color: var(--muted); font-size: 12px; font-weight: 800; }

        .login-card {
            border: 1px solid var(--line);
            border-radius: 30px;
            padding: 28px;
            background: rgba(8, 10, 24, .88);
            box-shadow: 0 34px 120px rgba(0,0,0,.52);
            backdrop-filter: blur(18px);
        }

        .login-head { margin-bottom: 24px; }
        .login-head p { margin: 0 0 10px; color: var(--cyan); font-size: 12px; font-weight: 900; letter-spacing: .18em; text-transform: uppercase; }
        .login-head h2 { margin: 0; font-size: 34px; line-height: 1; font-weight: 900; }
        .login-head span { display: block; margin-top: 10px; color: var(--muted); font-size: 14px; line-height: 1.5; }

        form { display: grid; gap: 16px; }
        label { display: grid; gap: 8px; color: rgba(255,255,255,.78); font-size: 13px; font-weight: 900; }

        .field {
            min-height: 58px;
            width: 100%;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 16px;
            outline: 0;
            padding: 0 16px;
            color: #fff;
            background: rgba(255,255,255,.08);
        }

        .field:focus {
            border-color: rgba(8,231,255,.66);
            box-shadow: 0 0 0 4px rgba(8,231,255,.10);
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }

        .row a { color: var(--cyan); text-decoration: none; }

        .submit {
            min-height: 58px;
            border: 0;
            border-radius: 16px;
            color: #061014;
            background: linear-gradient(135deg, var(--green), var(--cyan));
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 20px 60px rgba(8,231,255,.18);
        }

        .error { color: #ff9aac; font-size: 12px; font-weight: 800; }
        .status { margin-bottom: 16px; border: 1px solid rgba(49,247,164,.25); border-radius: 16px; padding: 12px; color: #b8ffd9; background: rgba(49,247,164,.10); font-size: 13px; font-weight: 800; }

        .back {
            display: grid;
            place-items: center;
            min-height: 48px;
            margin-top: 14px;
            border-radius: 14px;
            border: 1px solid var(--line);
            color: #fff;
            background: rgba(255,255,255,.07);
            text-decoration: none;
            font-weight: 900;
        }

        @media (max-width: 900px) {
            .page { grid-template-columns: 1fr; width: min(520px, calc(100% - 24px)); align-items: start; }
            .hero { min-height: auto; padding: 24px; gap: 42px; }
            .hero-copy h1 { font-size: 44px; }
            .metrics { grid-template-columns: 1fr 1fr 1fr; }
        }

        @media (max-width: 520px) {
            .metrics { grid-template-columns: 1fr; }
            .login-card { padding: 22px; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <a class="brand" href="{{ route('home') }}">
                <span class="mark">LG</span>
                <span>
                    <b>LG Universe</b>
                    <span>Panel administrativo</span>
                </span>
            </a>

            <div class="hero-copy">
                <h1>Acceso privado <span>sin ruido.</span></h1>
                <p>Controla NetCode, lectura de Excel, cuentas, perfiles y vencimientos desde un dashboard hecho para trabajar rapido.</p>
            </div>

            <div class="metrics">
                <div class="metric"><b>15</b><span>cuentas</span></div>
                <div class="metric"><b>75</b><span>perfiles</span></div>
                <div class="metric"><b>60s</b><span>busqueda</span></div>
            </div>
        </section>

        <section class="login-card">
            <div class="login-head">
                <p>Admin Login</p>
                <h2>Entrar</h2>
                <span>Usa tu cuenta administrativa para abrir el dashboard.</span>
            </div>

            @if (session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <label>
                    Correo
                    <input class="field" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Ingresa tu correo">
                    @error('email')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </label>

                <label>
                    Contrasena
                    <input class="field" name="password" type="password" required autocomplete="current-password" placeholder="Ingresa tu contrasena">
                    @error('password')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </label>

                <div class="row">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="remember">
                        Mantener sesion
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">Olvide mi contrasena</a>
                    @endif
                </div>

                <button class="submit" type="submit">Entrar al dashboard</button>
            </form>

            <a class="back" href="{{ route('home') }}">Volver al inicio</a>
        </section>
    </main>
</body>
</html>
