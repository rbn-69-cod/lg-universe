@php
    $netcodePage = $netcodePage ?? 'codigos';
    $adminTutorials = $adminTutorials ?? [];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#070712">
    <title>LG Universe | NetCode</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700;800;900&family=Russo+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg: #050711;
            --card: rgba(11, 13, 32, .88);
            --line: rgba(255,255,255,.13);
            --cyan: #08e7ff;
            --pink: #ff197b;
            --purple: #7e28ff;
            --blue: #27b8ff;
            --text: #fff;
            --muted: rgba(255,255,255,.62);
            --ok: #35f7a4;
            --danger: #ff4968;
        }

        * { box-sizing: border-box; }

        html, body {
            min-height: 100%;
            margin: 0;
            font-family: Inter, system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 8% 94%, rgba(255,25,123,.28), transparent 30%),
                radial-gradient(circle at 92% 12%, rgba(8,231,255,.16), transparent 28%),
                linear-gradient(135deg, #060614 0%, #06121a 100%);
            overflow-x: hidden;
        }

        button, input { font: inherit; }

        .loader {
            position: fixed;
            inset: 0;
            z-index: 100;
            display: grid;
            place-items: center;
            background: #050711;
            transition: opacity .35s ease, visibility .35s ease;
        }

        .loader.hide { opacity: 0; visibility: hidden; }

        .loader h1 {
            margin: 0 0 24px;
            font-family: "Russo One", sans-serif;
            font-size: clamp(44px, 8vw, 78px);
            font-style: italic;
            color: #fff;
            text-shadow: 8px 8px 0 var(--pink), 0 0 28px var(--cyan);
            text-align: center;
        }

        .loader p {
            margin: 0;
            text-align: center;
            color: var(--cyan);
            font-weight: 900;
            letter-spacing: .04em;
        }

        .page {
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 26px 14px;
            position: relative;
        }

        .page::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 10% 8%, rgba(255,25,123,.75) 1px, transparent 2px),
                radial-gradient(circle at 94% 22%, rgba(8,231,255,.9) 2px, transparent 3px),
                radial-gradient(circle at 78% 82%, rgba(8,231,255,.5) 2px, transparent 3px);
        }

        .panel {
            width: min(528px, 100%);
            border: 1px solid rgba(126,40,255,.44);
            border-radius: 28px;
            padding: 38px;
            background: linear-gradient(180deg, rgba(13,14,36,.94), rgba(8,8,24,.92));
            box-shadow: 0 34px 120px rgba(0,0,0,.55), 0 0 70px rgba(8,231,255,.08);
        }

        .brand {
            display: grid;
            place-items: center;
            gap: 18px;
            margin-bottom: 28px;
            text-align: center;
        }

        .ring {
            width: 102px;
            height: 102px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 900;
            font-size: 32px;
            background:
                radial-gradient(circle, rgba(8,231,255,.28), transparent 58%),
                conic-gradient(var(--cyan), var(--pink), #ffd400, transparent 72%);
            box-shadow: 0 0 32px rgba(8,231,255,.26);
        }

        .ring span {
            width: 74px;
            height: 74px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #111429;
        }

        .brand h1 {
            margin: 0;
            font-family: "Russo One", sans-serif;
            font-size: clamp(34px, 8vw, 46px);
            letter-spacing: .02em;
            color: var(--cyan);
            text-shadow: 0 0 22px rgba(8,231,255,.45);
        }

        .brand h1 b { color: #c62cff; }

        .brand p {
            margin: 0;
            color: rgba(255,255,255,.7);
            font-weight: 900;
            letter-spacing: .28em;
            font-size: 13px;
        }

        .line {
            width: 170px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(8,231,255,.45), transparent);
        }

        .stack { display: grid; gap: 14px; }

        .inputbox {
            min-height: 76px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 18px 0 22px;
            border-radius: 18px;
            background: rgba(0,0,0,.34);
            border: 1px solid rgba(255,255,255,.05);
        }

        .inputbox i {
            color: var(--cyan);
            font-size: 24px;
            text-shadow: 0 0 14px var(--cyan);
        }

        .inputbox input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
            color: #fff;
            font-size: 18px;
            min-width: 0;
        }

        .inputbox input::placeholder { color: rgba(255,255,255,.38); }

        .ghost {
            min-height: 52px;
            border-radius: 999px;
            border: 1px solid rgba(8,231,255,.42);
            background: rgba(8,231,255,.11);
            color: var(--cyan);
            font-weight: 900;
            cursor: pointer;
        }

        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 24px;
        }

        .mode-list {
            display: grid;
            gap: 14px;
            margin-top: 16px;
        }

        .bigbtn {
            min-height: 90px;
            border: 0;
            border-radius: 20px;
            color: white;
            font-weight: 900;
            font-size: 20px;
            cursor: pointer;
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            gap: 14px;
            padding: 18px;
            text-align: left;
            box-shadow: 0 18px 42px rgba(0,0,0,.24);
            transition: transform .16s ease, filter .16s ease;
        }

        .bigbtn:hover { transform: translateY(-3px); filter: brightness(1.08); }
        .bigbtn.selected { outline: 2px solid rgba(255,255,255,.72); outline-offset: 3px; }
        .home { background: linear-gradient(135deg, #ff5b1f, #ff167c); }
        .temp { background: linear-gradient(135deg, #14cfff, #06bdd8); }
        .access { background: linear-gradient(135deg, #a000ff, #ff007b); }
        .bigbtn i { font-size: 28px; filter: drop-shadow(0 0 10px rgba(255,255,255,.75)); }

        .section {
            margin-top: 18px;
            border: 1px solid rgba(8,231,255,.26);
            border-radius: 22px;
            padding: 18px;
            background: rgba(8,231,255,.06);
        }

        .section h2 {
            margin: 0 0 8px;
            font-size: 21px;
            font-weight: 900;
        }

        .section p {
            margin: 0 0 14px;
            color: var(--muted);
            line-height: 1.45;
            font-size: 14px;
        }

        .tutorial-media {
            min-height: 118px;
            border-radius: 18px;
            border: 1px dashed rgba(255,255,255,.22);
            background: rgba(0,0,0,.24);
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            gap: 14px;
            padding: 16px;
            color: rgba(255,255,255,.78);
            font-size: 14px;
            line-height: 1.45;
        }

        .tutorial-media i {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            color: var(--cyan);
            background: rgba(8,231,255,.12);
            font-size: 20px;
        }

        .stepbox { display: none; gap: 14px; }
        .stepbox.active { display: grid; }

        .tries {
            color: #ffd166;
            font-size: 12px;
            font-weight: 900;
            min-height: 18px;
        }

        .account {
            display: none;
            gap: 10px;
        }

        .account.active { display: grid; }
        .account-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }

        .cred {
            border-radius: 16px;
            padding: 13px;
            background: rgba(0,0,0,.26);
            border: 1px solid rgba(255,255,255,.08);
        }

        .cred span {
            display: block;
            font-size: 11px;
            color: var(--muted);
            font-weight: 900;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .cred b { overflow-wrap: anywhere; }

        .scan, .result {
            display: none;
            margin-top: 18px;
            border-radius: 22px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(0,0,0,.28);
            padding: 18px;
        }

        .scan.active, .result.active { display: block; }
        .timer { font-size: 42px; color: var(--cyan); font-weight: 900; text-align: center; }
        .scan-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 16px;
        }

        .danger-btn {
            min-height: 52px;
            border-radius: 999px;
            border: 1px solid rgba(255,73,104,.46);
            background: rgba(255,73,104,.12);
            color: #ff8fa1;
            font-weight: 900;
            cursor: pointer;
        }

        .result-value { text-align: center; font-weight: 900; overflow-wrap: anywhere; }
        .result-value.code { font-size: clamp(52px, 14vw, 82px); letter-spacing: .18em; color: var(--ok); }
        .result-value.link { font-size: 15px; color: var(--cyan); line-height: 1.5; }

        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(16px);
            opacity: 0;
            pointer-events: none;
            border-radius: 999px;
            background: rgba(0,0,0,.78);
            border: 1px solid rgba(255,255,255,.12);
            padding: 12px 16px;
            color: white;
            font-weight: 800;
            transition: .2s ease;
        }

        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        @media (max-width: 560px) {
            .panel { padding: 24px 18px; border-radius: 24px; }
            .actions { grid-template-columns: 1fr; gap: 14px; }
            .scan-actions { grid-template-columns: 1fr; }
            .account-grid { grid-template-columns: 1fr; }
            .bigbtn { min-height: 78px; }
            .brand p { letter-spacing: .16em; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="panel">
            <div class="brand">
                <div class="ring"><span>LG</span></div>
                <h1>{{ $netcodePage === 'acceso4' ? 'INICIO SESION' : 'NETCODE' }}</h1>
                <p>{{ $netcodePage === 'acceso4' ? 'CODIGO DE 4 DIGITOS' : 'HOGAR Y CODE TEMPORAL' }}</p>
                <div class="line"></div>
            </div>

            <div class="stack" id="freePanel" @if ($netcodePage === 'acceso4') style="display:none;" @endif>
                <section class="section" style="margin-top:0;">
                    <h2 id="modeTitle">Actualizar Hogar - Code Temporal</h2>
                    <p id="modeText">Esta pestana es solo para Code hogar y Code temporal. Inicio sesion de 4 digitos esta separado.</p>
                </section>

                <div class="inputbox">
                    <input id="emailLibre" type="email" inputmode="email" autocomplete="email" placeholder="Ingresa el correo de Netflix...">
                    <i class="fa-solid fa-envelope"></i>
                </div>

                <a class="ghost" href="{{ route('netcode.acceso4') }}" style="display:grid;place-items:center;text-decoration:none;">
                    <i class="fa-solid fa-circle-question"></i> No recuerdo mi correo
                </a>

                <div class="mode-list">
                    <button class="bigbtn home" type="button" data-mode="hogar">
                        <i class="fa-solid fa-house"></i>
                        <span>Code hogar<br><small>Actualizar hogar Netflix</small></span>
                    </button>
                    <button class="bigbtn temp" type="button" data-mode="temporal">
                        <i class="fa-solid fa-key"></i>
                        <span>Code temporal<br><small>Codigo o enlace temporal</small></span>
                    </button>
                </div>

                <div class="actions" style="margin-top: 12px;">
                    <button class="ghost" type="button" data-tutorial="hogar">
                        <i class="fa-solid fa-circle-play"></i> Tutorial hogar
                    </button>
                    <button class="ghost" type="button" data-tutorial="temporal">
                        <i class="fa-solid fa-circle-play"></i> Tutorial temporal
                    </button>
                </div>

                <div class="tutorial-media">
                    <i class="fa-solid fa-image"></i>
                    <span>Espacio para imagen o video tutorial de Code hogar y Code temporal.</span>
                </div>

                <a class="ghost" href="{{ route('netcode') }}" style="display:grid;place-items:center;text-decoration:none;">
                    <i class="fa-solid fa-arrow-left"></i> Volver al menu
                </a>
            </div>

            <section class="section" id="accessPanel" style="{{ $netcodePage === 'acceso4' ? 'display:block;' : 'display:none;' }}">
                <h2 id="accessTitle">Inicio sesion codigo 4 digitos</h2>
                <p id="accessText">Valida primero tu WhatsApp, luego tu nombre de perfil y finalmente tu PIN. Maximo 3 intentos por paso.</p>

                <div class="tutorial-media" style="margin-bottom:14px;">
                    <i class="fa-solid fa-video"></i>
                    <span>Espacio para imagen o video tutorial de Inicio sesion codigo 4 digitos.</span>
                </div>

                <div class="stepbox active" data-step-panel="whatsapp">
                    <div class="inputbox">
                        <input id="whatsappInput" type="tel" inputmode="tel" autocomplete="tel" placeholder="Ingresa tu WhatsApp...">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div class="tries" id="triesWhatsapp"></div>
                    <button class="ghost" type="button" data-validate-step="whatsapp">Validar WhatsApp</button>
                    <button class="ghost" type="button" data-tutorial="whatsapp">Ver tutorial</button>
                </div>

                <div class="stepbox" data-step-panel="nombre">
                    <div class="inputbox">
                        <input id="nameInput" type="text" autocomplete="off" placeholder="Ingresa el nombre del perfil...">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="tries" id="triesNombre"></div>
                    <button class="ghost" type="button" data-validate-step="nombre">Validar nombre</button>
                    <button class="ghost" type="button" data-tutorial="nombre">Ver tutorial</button>
                </div>

                <div class="stepbox" data-step-panel="pin">
                    <div class="inputbox">
                        <input id="pinInput" type="text" inputmode="numeric" maxlength="4" autocomplete="one-time-code" placeholder="Ingresa tu PIN de 4 digitos...">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div class="tries" id="triesPin"></div>
                    <button class="ghost" type="button" data-validate-step="pin">Validar PIN</button>
                    <button class="ghost" type="button" data-tutorial="pin">Ver tutorial</button>
                </div>

                <div class="account" id="accountPanel">
                    <div class="account-grid">
                        <div class="cred"><span>Correo Netflix</span><b id="accountEmail">-</b></div>
                        <div class="cred"><span>Contrasena</span><b id="accountPassword">-</b></div>
                        <div class="cred"><span>Perfil / PIN</span><b><span id="accountProfile">-</span> / <span id="accountPin">-</span></b></div>
                        <div class="cred"><span>WhatsApp</span><b id="accountPhone">-</b></div>
                        <div class="cred"><span>Vendedor</span><b id="accountSeller">-</b></div>
                        <div class="cred"><span>Costo</span><b id="accountCost">-</b></div>
                        <div class="cred"><span>Inicio</span><b id="accountStart">-</b></div>
                        <div class="cred"><span>Vence</span><b id="accountEnd">-</b></div>
                        <div class="cred"><span>Estado</span><b id="accountStatus">-</b></div>
                        <div class="cred"><span>Excel</span><b id="accountExcel">-</b></div>
                    </div>
                    <button class="bigbtn access" type="button" data-mode="acceso4" style="width:100%;">
                        <i class="fa-solid fa-key"></i>
                        <span>Buscar codigo<br>de 4 digitos</span>
                    </button>
                    <button class="ghost" type="button" data-tutorial="acceso4">Ver tutorial</button>
                </div>

                <a class="ghost" href="{{ route('netcode') }}" style="margin-top:14px;display:grid;place-items:center;text-decoration:none;">Volver al menu</a>
            </section>

            <section class="scan" id="scanPanel">
                <div class="timer" id="timer">60</div>
                <p id="scanStatus" style="text-align:center;color:var(--muted);font-weight:800;">Buscando correo reciente...</p>
                <div class="scan-actions">
                    <button class="ghost" type="button" id="changeSearchBtn">Cambiar correo/cuenta</button>
                    <button class="danger-btn" type="button" id="cancelSearchBtn">Cancelar busqueda</button>
                </div>
            </section>

            <section class="result" id="resultPanel">
                <div class="result-value" id="resultValue"></div>
                <p id="resultHint" style="text-align:center;color:var(--muted);"></p>
                <button class="ghost" type="button" id="copyBtn">Copiar resultado</button>
                <button class="ghost" type="button" id="newBtn" style="margin-top:10px;">Nuevo</button>
            </section>
        </section>
    </main>

    <div class="toast" id="toast">Listo</div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const API_URL = "{{ route('api.buscar-email') }}";
        const VALIDATE_URL = "{{ route('api.netflix-validar') }}";
        const NETCODE_PAGE = @js($netcodePage);
        const ADMIN_TUTORIALS = @json($adminTutorials);
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
        const MAX_TIME = 60;
        const POLL_MS = 10000;

        let currentMode = '';
        let currentEmail = '';
        let lastResult = '';
        let countdown = null;
        let polling = null;
        let timeLeft = MAX_TIME;
        let accessPurpose = 'acceso4';
        let verifiedAccount = null;
        let attempts = { whatsapp: 0, nombre: 0, pin: 0 };

        const $ = (id) => document.getElementById(id);
        const el = {
            loader: $('loader'),
            freePanel: $('freePanel'),
            accessPanel: $('accessPanel'),
            emailLibre: $('emailLibre'),
            whatsapp: $('whatsappInput'),
            name: $('nameInput'),
            pin: $('pinInput'),
            accountPanel: $('accountPanel'),
            accountEmail: $('accountEmail'),
            accountPassword: $('accountPassword'),
            accountProfile: $('accountProfile'),
            accountPin: $('accountPin'),
            accountPhone: $('accountPhone'),
            accountSeller: $('accountSeller'),
            accountCost: $('accountCost'),
            accountStart: $('accountStart'),
            accountEnd: $('accountEnd'),
            accountStatus: $('accountStatus'),
            accountExcel: $('accountExcel'),
            scanPanel: $('scanPanel'),
            resultPanel: $('resultPanel'),
            timer: $('timer'),
            scanStatus: $('scanStatus'),
            changeSearch: $('changeSearchBtn'),
            cancelSearch: $('cancelSearchBtn'),
            resultValue: $('resultValue'),
            resultHint: $('resultHint'),
            toast: $('toast'),
            modeTitle: $('modeTitle'),
            modeText: $('modeText'),
            accessTitle: $('accessTitle'),
            accessText: $('accessText'),
        };

        window.addEventListener('load', () => {
            if (el.loader) setTimeout(() => el.loader.classList.add('hide'), 650);
        });

        function toast(message) {
            el.toast.textContent = message;
            el.toast.classList.add('show');
            clearTimeout(toast._t);
            toast._t = setTimeout(() => el.toast.classList.remove('show'), 1600);
        }

        function digits(value) {
            return String(value || '').replace(/\D+/g, '');
        }

        function validEmail(value) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
        }

        function stepPanel(step) {
            document.querySelectorAll('[data-step-panel]').forEach((panel) => {
                panel.classList.toggle('active', panel.dataset.stepPanel === step);
            });
        }

        function setTryText(step) {
            const ids = { whatsapp: 'triesWhatsapp', nombre: 'triesNombre', pin: 'triesPin' };
            const left = Math.max(3 - attempts[step], 0);
            $(ids[step]).textContent = attempts[step] ? `Intentos restantes: ${left}` : '';
        }

        function openAccess(purpose) {
            accessPurpose = purpose;
            attempts = { whatsapp: 0, nombre: 0, pin: 0 };
            verifiedAccount = null;
            el.freePanel.style.display = 'none';
            el.accessPanel.style.display = 'block';
            el.accountPanel.classList.remove('active');
            stepPanel('whatsapp');
            ['triesWhatsapp', 'triesNombre', 'triesPin'].forEach((id) => $(id).textContent = '');
            el.accessTitle.textContent = purpose === 'forgot' ? 'No recuerdo mi correo' : 'Inicio sesion codigo 4 digitos';
            el.accessText.textContent = purpose === 'forgot'
                ? 'Valida WhatsApp, nombre y PIN para mostrar el correo de tu cuenta Netflix.'
                : 'Valida WhatsApp, nombre y PIN para buscar el codigo unico de 4 digitos.';
            setTimeout(() => el.whatsapp.focus(), 120);
        }

        function closeAccess() {
            stop();
            el.freePanel.style.display = 'grid';
            el.accessPanel.style.display = 'none';
            el.scanPanel.classList.remove('active');
            el.resultPanel.classList.remove('active');
        }

        async function validateStep(step) {
            if (attempts[step] >= 3) {
                toast('Limite de intentos alcanzado');
                return;
            }

            const body = {
                step,
                numero: digits(el.whatsapp.value),
                nombre_perfil: el.name.value.trim(),
                pin: el.pin.value.trim(),
            };

            if (step === 'whatsapp' && body.numero.length < 6) {
                toast('Ingresa tu WhatsApp');
                return;
            }
            if (step === 'nombre' && !body.nombre_perfil) {
                toast('Ingresa el nombre del perfil');
                return;
            }
            if (step === 'pin' && !/^\d{4}$/.test(body.pin)) {
                toast('Ingresa un PIN de 4 digitos');
                return;
            }

            try {
                const response = await fetch(VALIDATE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify(body),
                });
                const data = await response.json();

                if (!response.ok || data.status !== 'success') {
                    attempts[step] += 1;
                    setTryText(step);
                    Swal.fire({
                        title: 'Validacion fallida',
                        text: data.message || 'Dato incorrecto.',
                        icon: 'warning',
                        background: '#111426',
                        color: '#fff',
                        confirmButtonColor: '#08e7ff',
                    });
                    return;
                }

                toast('Validado');
                if (step === 'whatsapp') stepPanel('nombre');
                if (step === 'nombre') stepPanel('pin');
                if (step === 'pin') {
                    verifiedAccount = data;
                    currentEmail = data.cuenta.email;
                    el.accountEmail.textContent = data.cuenta.email || '-';
                    el.accountPassword.textContent = data.cuenta.password || '-';
                    el.accountProfile.textContent = data.perfil.nombre || '-';
                    el.accountPin.textContent = data.perfil.pin || '-';
                    el.accountPhone.textContent = data.perfil.numero || '-';
                    el.accountSeller.textContent = data.perfil.vendedor || '-';
                    el.accountCost.textContent = data.perfil.costo ? `S/ ${data.perfil.costo}` : '-';
                    el.accountStart.textContent = data.perfil.fecha_inicio || '-';
                    el.accountEnd.textContent = data.perfil.vence || data.perfil.fecha_fin || '-';
                    el.accountStatus.textContent = data.perfil.estado || (data.perfil.ocupado ? 'Activo' : 'Libre');
                    el.accountExcel.textContent = `${data.perfil.hoja_excel || '-'} #${data.perfil.fila_excel || '-'}`;
                    document.querySelectorAll('[data-step-panel]').forEach((panel) => panel.classList.remove('active'));
                    el.accountPanel.classList.add('active');
                }
            } catch (error) {
                console.error(error);
                toast('Error validando');
            }
        }

        async function start(mode) {
            currentMode = mode;

            if (mode === 'acceso4') {
                if (!verifiedAccount?.cuenta?.email) {
                    toast('Primero valida WhatsApp, nombre y PIN');
                    return;
                }
                currentEmail = verifiedAccount.cuenta.email;
            } else {
                currentEmail = el.emailLibre.value.trim().toLowerCase();
                if (!validEmail(currentEmail)) {
                    toast('Ingresa el correo de Netflix');
                    el.emailLibre.focus();
                    return;
                }
            }

            const labels = {
                hogar: 'Code hogar',
                temporal: 'Code temporal',
                acceso4: 'Inicio sesion codigo 4 digitos',
            };

            const confirmation = await Swal.fire({
                title: labels[mode],
                text: mode === 'acceso4' ? 'Confirma que Netflix ya pidio el codigo de login.' : 'Confirma que Netflix ya envio el correo.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Buscar ahora',
                cancelButtonText: 'Cancelar',
                background: '#111426',
                color: '#fff',
                confirmButtonColor: '#08e7ff',
            });

            if (!confirmation.isConfirmed) return;

            lastResult = '';
            timeLeft = MAX_TIME;
            el.freePanel.style.display = 'none';
            el.accessPanel.style.display = 'none';
            el.resultPanel.classList.remove('active');
            el.scanPanel.classList.add('active');
            el.timer.textContent = timeLeft;
            el.scanStatus.textContent = `Buscando ${labels[mode].toLowerCase()}...`;

            tick();
            checkServer();
            countdown = setInterval(tick, 1000);
            polling = setInterval(checkServer, POLL_MS);
        }

        function tick() {
            el.timer.textContent = Math.max(timeLeft, 0);
            if (timeLeft === 30) el.scanStatus.textContent = 'Revisando correos recientes...';
            if (timeLeft === 12) el.scanStatus.textContent = 'Ultima busqueda...';
            if (timeLeft <= 0) {
                stop();
                Swal.fire({
                    title: 'No encontrado',
                    text: 'Reenvia el correo desde Netflix e intenta otra vez.',
                    icon: 'warning',
                    background: '#111426',
                    color: '#fff',
                    confirmButtonColor: '#08e7ff',
                }).then(reset);
                return;
            }
            timeLeft -= 1;
        }

        async function checkServer() {
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({ email: currentEmail, subject: currentMode }),
                });
                const data = await response.json();
                if (data.status === 'success' && data.valor_extraido) {
                    showResult(String(data.valor_extraido), data.tipo || '');
                }
            } catch (error) {
                console.error(error);
            }
        }

        function showResult(value, type) {
            stop();
            lastResult = value.trim();
            const isCode = type === 'codigo' || /^\d{4,8}$/.test(lastResult);
            const isLink = /^https?:\/\//i.test(lastResult);
            el.scanPanel.classList.remove('active');
            el.resultPanel.classList.add('active');
            el.resultValue.className = `result-value ${isCode ? 'code' : 'link'}`;
            el.resultValue.textContent = lastResult;
            el.resultHint.textContent = isLink ? 'Abre el enlace cuanto antes.' : 'Copia el codigo y usalo antes de que venza.';
            $('copyBtn').textContent = isLink ? 'Abrir enlace' : 'Copiar codigo';
        }

        function stop() {
            clearInterval(countdown);
            clearInterval(polling);
            countdown = null;
            polling = null;
        }

        function reset() {
            stop();
            el.resultPanel.classList.remove('active');
            el.scanPanel.classList.remove('active');
            if (currentMode === 'acceso4') {
                el.accessPanel.style.display = 'block';
                el.accountPanel.classList.add('active');
            } else {
                el.freePanel.style.display = 'grid';
            }
        }

        function cancelSearch() {
            stop();
            el.scanPanel.classList.remove('active');
            el.resultPanel.classList.remove('active');
            lastResult = '';

            if (currentMode === 'acceso4') {
                el.accessPanel.style.display = 'block';
                el.accountPanel.classList.add('active');
                toast('Busqueda cancelada');
                return;
            }

            el.freePanel.style.display = 'grid';
            toast('Busqueda cancelada');
        }

        function changeSearchData() {
            stop();
            el.scanPanel.classList.remove('active');
            el.resultPanel.classList.remove('active');
            lastResult = '';

            if (currentMode === 'acceso4') {
                el.accessPanel.style.display = 'block';
                el.accountPanel.classList.add('active');
                toast('Puedes revisar la cuenta validada');
                return;
            }

            el.freePanel.style.display = 'grid';
            el.emailLibre.value = currentEmail || el.emailLibre.value;
            setTimeout(() => {
                el.emailLibre.focus();
                el.emailLibre.select();
            }, 80);
            toast('Cambia el correo y busca de nuevo');
        }

        async function copyOrOpen() {
            if (!lastResult) return;
            if (/^https?:\/\//i.test(lastResult)) {
                window.open(lastResult, '_blank', 'noopener,noreferrer');
                return;
            }
            try {
                await navigator.clipboard.writeText(lastResult);
                toast('Copiado');
            } catch {
                Swal.fire({ title: 'Resultado', text: lastResult, background: '#111426', color: '#fff' });
            }
        }

        document.querySelectorAll('[data-mode]').forEach((button) => button.addEventListener('click', () => start(button.dataset.mode)));
        document.querySelectorAll('[data-validate-step]').forEach((button) => button.addEventListener('click', () => validateStep(button.dataset.validateStep)));
        const tutorials = {
            general: {
                title: 'Tutorial NetCode',
                steps: [
                    'Elige la pestana correcta.',
                    'Confirma que Netflix ya envio el correo.',
                    'Presiona buscar y espera hasta 60 segundos.',
                ],
                mediaText: 'Aqui puedes poner una imagen o video general del flujo.'
            },
            whatsapp: {
                title: 'Tutorial WhatsApp',
                steps: [
                    'Ingresa el numero del cliente tal como esta en el Excel.',
                    'Puedes escribirlo con espacios; el sistema compara solo numeros.',
                    'Si falla, revisa que hayas presionado Leer Excel ahora despues del cambio.',
                ],
                mediaText: 'Aqui puedes poner imagen del campo WhatsApp o un video corto.'
            },
            hogar: {
                title: 'Tutorial link de hogar',
                steps: [
                    'Ingresa el correo Netflix de la cuenta.',
                    'En Netflix pide actualizar hogar.',
                    'Presiona Code hogar.',
                    'Cuando salga el enlace, abrelo y confirma rapido.',
                ],
                mediaText: 'Aqui puedes poner imagen o video para actualizar hogar.'
            },
            temporal: {
                title: 'Tutorial codigo temporal',
                steps: [
                    'Ingresa el correo Netflix de la cuenta.',
                    'Pide a Netflix enviar el codigo temporal.',
                    'Presiona Code temporal.',
                    'Copia el codigo o abre el enlace que aparezca.',
                ],
                mediaText: 'Aqui puedes poner imagen o video del codigo temporal.'
            },
            nombre: {
                title: 'Tutorial perfil',
                steps: [
                    'Escribe el nombre exacto del perfil.',
                    'El sistema ignora mayusculas, espacios y tildes.',
                    'Si no valida, revisa que ese perfil pertenezca al WhatsApp ingresado.',
                ],
                mediaText: 'Aqui puedes poner imagen del nombre de perfil.'
            },
            pin: {
                title: 'Tutorial PIN',
                steps: [
                    'Ingresa el PIN del perfil.',
                    'Si coincide, se mostraran correo, contrasena, vencimiento y datos completos.',
                    'Luego presiona Buscar codigo de 4 digitos.',
                ],
                mediaText: 'Aqui puedes poner imagen o video de donde ver el PIN.'
            },
            acceso4: {
                title: 'Tutorial codigo de acceso 4 digitos',
                steps: [
                    'Valida WhatsApp, perfil y PIN.',
                    'Confirma que Netflix ya pidio el codigo de inicio de sesion.',
                    'Presiona Buscar codigo de 4 digitos.',
                    'Copia el codigo antes de que venza.',
                ],
                mediaText: 'Aqui puedes poner imagen o video del inicio de sesion con 4 digitos.'
            }
        };

        Object.entries(ADMIN_TUTORIALS).forEach(([key, tutorial]) => {
            const hasSteps = Array.isArray(tutorial.steps) && tutorial.steps.length > 0;
            const merged = {
                ...(tutorials[key] || {}),
                title: tutorial.title || tutorials[key]?.title || 'Tutorial',
                steps: hasSteps ? tutorial.steps : (tutorials[key]?.steps || []),
                mediaText: tutorials[key]?.mediaText || 'El admin aun no subio archivo para este tutorial.',
            };

            if (tutorial.media_url && tutorial.media_type === 'video') {
                merged.video = tutorial.media_url;
                delete merged.image;
            }

            if (tutorial.media_url && tutorial.media_type === 'image') {
                merged.image = tutorial.media_url;
                delete merged.video;
            }

            tutorials[key] = merged;
        });

        function tutorialHtml(tutorial) {
            const steps = tutorial.steps.map((step, index) => `<li><b>${index + 1}.</b> ${step}</li>`).join('');
            const media = tutorial.video
                ? `<video src="${tutorial.video}" controls playsinline style="width:100%;border-radius:14px;margin-top:14px;"></video>`
                : tutorial.image
                    ? `<img src="${tutorial.image}" alt="${tutorial.title}" style="width:100%;border-radius:14px;margin-top:14px;">`
                    : `<div style="margin-top:14px;border:1px dashed rgba(255,255,255,.24);border-radius:14px;padding:14px;color:rgba(255,255,255,.72);text-align:left;"><b>Imagen/video:</b><br>${tutorial.mediaText}</div>`;

            return `<ol style="text-align:left;display:grid;gap:10px;margin:0;padding-left:0;list-style:none;">${steps}</ol>${media}`;
        }

        document.querySelectorAll('[data-tutorial]').forEach((button) => button.addEventListener('click', () => {
            const tutorial = tutorials[button.dataset.tutorial] || tutorials.general;
            Swal.fire({
                title: tutorial.title,
                html: tutorialHtml(tutorial),
                icon: 'info',
                background: '#111426',
                color: '#fff',
                confirmButtonColor: '#08e7ff',
            });
        }));

        if ($('openAccessBtn')) $('openAccessBtn').addEventListener('click', () => openAccess('acceso4'));
        if ($('forgotBtn')) $('forgotBtn').addEventListener('click', () => openAccess('forgot'));
        if ($('backBtn')) $('backBtn').addEventListener('click', closeAccess);
        el.changeSearch.addEventListener('click', changeSearchData);
        el.cancelSearch.addEventListener('click', cancelSearch);
        $('copyBtn').addEventListener('click', copyOrOpen);
        $('newBtn').addEventListener('click', reset);
        [el.whatsapp, el.name, el.pin].forEach((input) => input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            const active = document.querySelector('.stepbox.active')?.dataset.stepPanel;
            if (active) validateStep(active);
        }));
        el.name.addEventListener('input', () => el.name.value = el.name.value.toUpperCase());
        el.pin.addEventListener('input', () => el.pin.value = digits(el.pin.value).slice(0, 4));

        function markSelectedMode(mode) {
            document.querySelectorAll('[data-mode]').forEach((button) => {
                button.classList.toggle('selected', button.dataset.mode === mode);
            });

            const copy = {
                hogar: [
                    'Code hogar',
                    'Ingresa el correo de Netflix y presiona Code hogar para buscar el enlace de actualizacion de hogar.'
                ],
                temporal: [
                    'Code temporal',
                    'Ingresa el correo de Netflix y presiona Code temporal para buscar el codigo o enlace temporal.'
                ],
                acceso4: [
                    'Inicio sesion codigo 4 digitos',
                    'Este flujo es aparte: valida WhatsApp, perfil y PIN antes de buscar el codigo de login.'
                ],
            };

            if (copy[mode]) {
                el.modeTitle.textContent = copy[mode][0];
                el.modeText.textContent = copy[mode][1];
            }
        }

        const selectedMode = new URLSearchParams(window.location.search).get('modo');
        if (NETCODE_PAGE === 'acceso4' || selectedMode === 'acceso4') setTimeout(() => openAccess('acceso4'), 0);
        if (selectedMode === 'hogar' || selectedMode === 'temporal') {
            markSelectedMode(selectedMode);
            setTimeout(() => el.emailLibre.focus(), 300);
        }
    </script>
</body>
</html>

