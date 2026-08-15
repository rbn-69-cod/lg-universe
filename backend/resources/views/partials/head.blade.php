<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

{{-- Título por defecto LG Universe --}}
<title>{{ $title ?? 'LG Universe - Bienvenido' }}</title>

{{-- Color de barra / tema del navegador (quitar rojo Laravel) --}}
<meta name="theme-color" content="#020617">

{{-- Favicon NUEVO (o el que tú quieras) --}}
<link rel="icon" type="image/png" href="{{ asset('img/lg-universe-icon.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
