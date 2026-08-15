<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#070712">
    <title>LG Universe | NetCode</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@700;800;900&family=Russo+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --cyan: #08e7ff;
            --pink: #ff197b;
            --purple: #7e28ff;
            --text: #fff;
            --muted: rgba(255,255,255,.78);
        }

        * { box-sizing: border-box; }

        html, body {
            min-height: 100%;
            margin: 0;
            font-family: Inter, system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 7% 9%, rgba(255,25,123,.34), transparent 34%),
                radial-gradient(circle at 92% 90%, rgba(8,231,255,.22), transparent 36%),
                linear-gradient(135deg, #08000d 0%, #050711 54%, #082331 100%);
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 4% 8%, rgba(8,231,255,.8) 3px, transparent 4px),
                radial-gradient(circle at 22% 14%, rgba(255,25,123,.8) 2px, transparent 3px),
                radial-gradient(circle at 81% 3%, rgba(8,231,255,.8) 1px, transparent 2px),
                radial-gradient(circle at 96% 27%, rgba(8,231,255,.7) 2px, transparent 3px),
                radial-gradient(circle at 7% 69%, rgba(8,231,255,.5) 1px, transparent 2px);
        }

        a, button { font: inherit; }

        .loader {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: grid;
            place-items: center;
            background: #050711;
            transition: opacity .35s ease, visibility .35s ease;
        }

        .loader.hide { opacity: 0; visibility: hidden; }

        .loader h1 {
            margin: 0 0 18px;
            font-family: "Russo One", sans-serif;
            font-size: clamp(44px, 8vw, 82px);
            font-style: italic;
            color: #fff;
            text-shadow: 8px 8px 0 var(--pink), 0 0 28px var(--cyan);
            text-align: center;
        }

        .loader p {
            margin: 0;
            color: var(--cyan);
            font-weight: 900;
            text-align: center;
        }

        .page {
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 34px 14px;
            position: relative;
            z-index: 1;
        }

        .panel {
            width: min(496px, 100%);
            min-height: 804px;
            border-radius: 42px;
            padding: 58px 38px 38px;
            display: grid;
            align-content: start;
            gap: 26px;
            background: linear-gradient(164deg, #8020ff 0%, #355ff2 46%, #10d8dc 100%);
            box-shadow: 0 36px 130px rgba(0,0,0,.62), 0 0 80px rgba(8,231,255,.16);
            position: relative;
        }

        .admin-logo {
            position: absolute;
            top: 22px;
            left: 22px;
            width: 46px;
            height: 46px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            color: #fff;
            text-decoration: none;
            font-weight: 900;
            background: rgba(255,255,255,.14);
            border: 1px solid rgba(255,255,255,.24);
            box-shadow: 0 18px 46px rgba(0,0,0,.16);
        }

        .brand {
            text-align: center;
            display: grid;
            gap: 16px;
        }

        .brand h1 {
            margin: 0;
            font-family: "Russo One", sans-serif;
            font-size: clamp(38px, 10vw, 52px);
            font-style: italic;
            color: #fff;
            text-shadow: 8px 8px 0 var(--pink), 0 0 18px rgba(255,255,255,.18);
            white-space: nowrap;
        }

        .brand p {
            margin: 0 auto;
            max-width: 390px;
            font-size: clamp(20px, 5vw, 25px);
            line-height: 1.25;
            font-weight: 900;
        }

        .options {
            display: grid;
            gap: 24px;
            margin-top: 22px;
        }

        .option {
            min-height: 128px;
            border-radius: 28px;
            border: 2px solid rgba(255,255,255,.24);
            color: #fff;
            text-decoration: none;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 18px;
            padding: 22px 24px;
            box-shadow: 0 24px 58px rgba(0,0,0,.18), inset 0 0 0 1px rgba(255,255,255,.08);
            transition: transform .18s ease, filter .18s ease;
        }

        .option:hover { transform: translateY(-3px); filter: brightness(1.08); }
        .option.pink { background: linear-gradient(135deg, #9b00ff 0%, #ff0077 100%); }
        .option.blue { background: linear-gradient(135deg, #16d7ff 0%, #6a20ff 100%); }
        .option.lock { background: linear-gradient(135deg, #171b34 0%, #a000ff 48%, #ff197b 100%); }

        .option .icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            font-size: 34px;
            filter: drop-shadow(0 0 14px rgba(255,255,255,.8));
        }

        .option b {
            display: block;
            font-size: clamp(21px, 5vw, 27px);
            line-height: 1.08;
            margin-bottom: 6px;
        }

        .option span {
            display: block;
            color: rgba(255,255,255,.90);
            font-size: 16px;
            line-height: 1.25;
        }

        .option .go { font-size: 31px; }

        .notice {
            margin-top: 12px;
            min-height: 92px;
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            gap: 16px;
            padding: 18px 22px;
            border-radius: 26px;
            border: 2px solid rgba(8,231,255,.80);
            background: rgba(6, 19, 40, .20);
            color: #fff;
            font-weight: 900;
            line-height: 1.28;
        }

        .notice i {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: var(--cyan);
            color: #00687a;
        }

        .footer {
            margin-top: 18px;
            text-align: center;
            font-weight: 900;
            color: #fff;
            text-shadow: 2px 2px 0 var(--pink);
        }

        .bot {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: 76px;
            height: 76px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: #fff;
            text-decoration: none;
            font-size: 32px;
            background: linear-gradient(135deg, #13dfff, #7e28ff);
            box-shadow: 0 22px 70px rgba(8,231,255,.25);
        }

        @media (max-width: 560px) {
            .page { padding: 18px 12px; align-items: start; }
            .panel { min-height: calc(100dvh - 36px); border-radius: 32px; padding: 42px 22px 28px; }
            .option { min-height: 112px; padding: 18px; grid-template-columns: auto 1fr auto; }
            .option .icon { font-size: 28px; }
            .bot { width: 62px; height: 62px; font-size: 25px; right: 16px; bottom: 16px; }
        }
    </style>
</head>
<body>
    <div class="loader" id="loader">
        <div>
            <h1>LG UNIVERSE</h1>
            <p>Cargando acceso...</p>
        </div>
    </div>

    <main class="page">
        <section class="panel">
            <a class="admin-logo" href="{{ route('login') }}" aria-label="Login administrador">LG</a>

            <div class="brand">
                <h1>LG UNIVERSE</h1>
                <p>Tu puerta de acceso al entretenimiento premium sin limites</p>
            </div>

            <div class="options">
                <a class="option pink" href="{{ route('netcode.codigos') }}">
                    <div class="icon"><i class="fa-solid fa-bolt"></i></div>
                    <div>
                        <b>Actualizar Hogar - Code Temporal</b>
                        <span>Solucion cuando tu dispositivo no forma parte del hogar.</span>
                    </div>
                    <i class="fa-solid fa-chevron-right go"></i>
                </a>

                <a class="option lock" href="{{ route('netcode.acceso4') }}">
                    <div class="icon"><i class="fa-solid fa-lock"></i></div>
                    <div>
                        <b>Inicio sesion 4 digitos</b>
                        <span>Valida WhatsApp, perfil y PIN antes de buscar el codigo.</span>
                    </div>
                    <i class="fa-solid fa-chevron-right go"></i>
                </a>

                <a class="option blue" href="{{ url('/plataformas') }}">
                    <div class="icon"><i class="fa-solid fa-star"></i></div>
                    <div>
                        <b>Mi Catalogo</b>
                        <span>Explorar otras plataformas disponibles.</span>
                    </div>
                    <i class="fa-solid fa-chevron-right go"></i>
                </a>
            </div>

            <div class="notice">
                <i class="fa-solid fa-exclamation"></i>
                <span>Solucion rapida ONLINE y solucionalo rapido desde cualquier dispositivo.</span>
            </div>

            <div class="footer">Calidad Garantizada - Soporte VIP</div>
        </section>
    </main>

    <a class="bot" href="{{ route('netcode.codigos') }}" aria-label="Abrir ayuda">
        <i class="fa-solid fa-robot"></i>
    </a>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => document.getElementById('loader').classList.add('hide'), 650);
        });
    </script>
</body>
</html>
