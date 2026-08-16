<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Abrir bot seguro</title>
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #f8fbff;
            background:
                radial-gradient(circle at 15% 10%, rgba(49, 247, 164, .18), transparent 30%),
                radial-gradient(circle at 90% 12%, rgba(39, 224, 255, .16), transparent 34%),
                linear-gradient(180deg, #080817 0%, #05050d 100%);
        }

        .panel {
            width: min(460px, calc(100% - 32px));
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 22px;
            padding: 24px;
            background: rgba(10, 13, 28, .9);
            box-shadow: 0 28px 100px rgba(0, 0, 0, .38);
        }

        .mark {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            border-radius: 16px;
            font-weight: 900;
            color: #061014;
            background: linear-gradient(135deg, #31f7a4, #27e0ff);
        }

        h1 {
            margin: 18px 0 8px;
            font-size: 28px;
            line-height: 1;
        }

        p {
            margin: 0;
            color: rgba(248, 251, 255, .68);
            line-height: 1.6;
        }

        .host {
            margin-top: 16px;
            padding: 12px;
            border-radius: 14px;
            color: #31f7a4;
            background: rgba(49, 247, 164, .08);
            overflow-wrap: anywhere;
            font-weight: 800;
        }

        .actions {
            display: grid;
            gap: 10px;
            margin-top: 20px;
        }

        a {
            min-height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 900;
        }

        .primary {
            color: #061014;
            background: linear-gradient(135deg, #31f7a4, #27e0ff);
        }

        .secondary {
            color: #f8fbff;
            border: 1px solid rgba(255, 255, 255, .14);
            background: rgba(255, 255, 255, .075);
        }
    </style>
</head>
<body>
    <main class="panel">
        <div class="mark">LG</div>
        <h1>Link protegido</h1>
        <p>{{ $description ?? 'Esta salida protege el enlace externo. Revisa el destino antes de continuar.' }}</p>
        <div class="host">{{ $host }}</div>
        <div class="actions">
            <a class="primary" href="{{ $targetUrl }}" target="_blank" rel="noopener noreferrer">Abrir bot externo</a>
            <a class="secondary" href="{{ $backUrl ?? url('/dashboard') }}">{{ $backLabel ?? 'Volver al dashboard' }}</a>
        </div>
    </main>
</body>
</html>
