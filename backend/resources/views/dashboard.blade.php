<x-layouts.app :title="__('Dashboard')">
    <style>
        .board {
            --bg: #050711;
            --panel: rgba(10, 13, 28, .86);
            --panel2: rgba(255,255,255,.065);
            --line: rgba(255,255,255,.13);
            --text: #f8fbff;
            --muted: rgba(248,251,255,.62);
            --cyan: #27e0ff;
            --green: #31f7a4;
            --pink: #ff3d88;
            --warn: #ffd166;
            color: var(--text);
            margin: -24px;
            min-height: 100dvh;
            padding: 24px;
            background:
                radial-gradient(circle at 10% 8%, rgba(255,61,136,.20), transparent 32%),
                radial-gradient(circle at 92% 12%, rgba(39,224,255,.18), transparent 34%),
                radial-gradient(circle at 50% 96%, rgba(49,247,164,.10), transparent 38%),
                linear-gradient(180deg, #080817 0%, #05050d 100%);
        }

        .shell { width: min(1280px, 100%); margin: 0 auto; display: grid; gap: 16px; }
        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(280px, 360px);
            gap: 16px;
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(39,224,255,.12), rgba(255,61,136,.08)), var(--panel);
            box-shadow: 0 28px 100px rgba(0,0,0,.36);
            overflow: hidden;
            position: relative;
        }
        .hero::after {
            content: "LG";
            position: absolute;
            right: 18px;
            bottom: -22px;
            font-size: 118px;
            line-height: 1;
            font-weight: 900;
            color: rgba(255,255,255,.04);
            pointer-events: none;
        }
        .brand { display: flex; align-items: center; gap: 12px; }
        .mark { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; font-weight: 900; border: 1px solid var(--line); background: linear-gradient(135deg, rgba(49,247,164,.20), rgba(39,224,255,.16)); }
        .brand h1 { margin: 0; font-size: 24px; line-height: 1; font-weight: 900; }
        .brand p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
        .hero-title { margin: 22px 0 0; font-size: clamp(34px, 6vw, 64px); line-height: .9; font-weight: 900; letter-spacing: 0; }
        .hero-title span { color: var(--cyan); }
        .hero-copy { max-width: 760px; margin: 12px 0 0; color: var(--muted); line-height: 1.65; font-size: 15px; }
        .hero-actions { display: grid; gap: 10px; align-content: center; position: relative; z-index: 1; }
        .btn {
            min-height: 46px;
            border-radius: 12px;
            border: 1px solid var(--line);
            color: var(--text);
            background: rgba(255,255,255,.075);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            cursor: pointer;
        }
        .btn.primary { color: #061014; border-color: transparent; background: linear-gradient(135deg, var(--green), var(--cyan)); }
        .btn.danger { color: #ff9aac; border-color: rgba(255,61,136,.34); background: rgba(255,61,136,.10); }
        .btn.full { width: 100%; }

        .metrics { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; }
        .metric {
            min-height: 114px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
            padding: 16px;
            box-shadow: 0 22px 70px rgba(0,0,0,.22);
            overflow: hidden;
            position: relative;
        }
        .metric::after { content: ""; position: absolute; right: 14px; bottom: 14px; width: 42px; height: 42px; border-radius: 14px; background: linear-gradient(135deg, rgba(49,247,164,.18), rgba(39,224,255,.10)); }
        .metric span { color: var(--muted); font-size: 11px; font-weight: 900; text-transform: uppercase; }
        .metric b { display: block; margin-top: 10px; font-size: clamp(26px, 4vw, 40px); line-height: 1; }
        .metric small { display: block; margin-top: 8px; color: var(--muted); }

        .quick { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .quick-card {
            min-height: 154px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255,255,255,.085), rgba(255,255,255,.035));
            padding: 16px;
            display: grid;
            align-content: space-between;
            gap: 16px;
            box-shadow: 0 22px 70px rgba(0,0,0,.22);
        }
        .quick-card h2 { margin: 0; font-size: 19px; font-weight: 900; }
        .quick-card p { margin: 8px 0 0; color: var(--muted); line-height: 1.5; font-size: 13px; }

        .section {
            border: 1px solid var(--line);
            border-radius: 20px;
            background: var(--panel);
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(0,0,0,.24);
        }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid var(--line);
            flex-wrap: wrap;
        }
        .section-head h2 { margin: 0; font-size: 19px; font-weight: 900; }
        .section-head p { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
        .accounts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; padding: 16px; }
        .account {
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 16px;
            background: var(--panel2);
            overflow: hidden;
        }
        .account-top { padding: 14px; border-bottom: 1px solid rgba(255,255,255,.08); display: grid; gap: 8px; }
        .account-email { font-weight: 900; overflow-wrap: anywhere; }
        .password { color: var(--muted); font-size: 13px; overflow-wrap: anywhere; }
        .tags { display: flex; gap: 6px; flex-wrap: wrap; }
        .tag { min-height: 25px; display: inline-flex; align-items: center; border: 1px solid rgba(255,255,255,.12); border-radius: 999px; padding: 0 9px; font-size: 11px; font-weight: 900; color: rgba(248,251,255,.78); background: rgba(255,255,255,.065); }
        .tag.ok { color: var(--green); border-color: rgba(49,247,164,.24); background: rgba(49,247,164,.08); }
        .tag.warn { color: var(--warn); border-color: rgba(255,209,102,.24); background: rgba(255,209,102,.08); }
        .profiles { display: grid; }
        .profile {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            display: grid;
            gap: 8px;
        }
        .profile:last-child { border-bottom: 0; }
        .profile-title { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .profile-title b { overflow-wrap: anywhere; }
        .profile-data { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 10px; color: var(--muted); font-size: 12px; }
        .profile-data b { color: var(--text); overflow-wrap: anywhere; }

        .table-wrap { overflow: auto; }
        table { width: 100%; min-width: 1060px; border-collapse: collapse; }
        th, td { padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,.08); text-align: left; vertical-align: top; font-size: 13px; }
        th { color: rgba(248,251,255,.68); background: rgba(255,255,255,.04); font-size: 11px; text-transform: uppercase; }
        td { color: rgba(248,251,255,.90); }
        .empty { padding: 28px; color: var(--muted); text-align: center; }

        @media (max-width: 1080px) {
            .hero { grid-template-columns: 1fr; }
            .metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .quick { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .accounts { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .board { margin: -16px; padding: 16px; }
            .metrics, .quick { grid-template-columns: 1fr; }
            .btn { width: 100%; }
        }
    </style>

    <div class="board">
        <div class="shell">
            <section class="hero">
                <div>
                    <div class="brand">
                        <div class="mark">LG</div>
                        <div>
                            <h1>Dashboard</h1>
                            <p>{{ auth()->user()->name }} - panel privado</p>
                        </div>
                    </div>

                    <div class="hero-title">Operacion <span>Netflix</span></div>
                    <p class="hero-copy">Lee el Excel cuando hagas cambios, revisa cuentas y perfiles, administra tutoriales y abre NetCode sin buscar menus escondidos.</p>
                </div>

                <div class="hero-actions">
                    <form method="POST" action="{{ route('admin.excel-import-ranges.sync') }}">
                        @csrf
                        <button class="btn primary full" type="submit">Leer Excel ahora</button>
                    </form>
                    <a class="btn full" href="{{ route('netcode.codigos') }}" target="_blank">Hogar / Code temporal</a>
                    <a class="btn full" href="{{ route('netcode.acceso4') }}" target="_blank">Inicio sesion 4 digitos</a>
                    <a class="btn full" href="{{ route('admin.tutoriales.index') }}">Subir tutoriales</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn danger full" type="submit">Cerrar sesion</button>
                    </form>
                </div>
            </section>

            <section class="metrics">
                <div class="metric"><span>Cuentas</span><b>{{ $stats['cuentas'] }}</b><small>{{ $stats['capacidad_cuentas'] }} max.</small></div>
                <div class="metric"><span>Perfiles</span><b>{{ $stats['perfiles'] }}</b><small>{{ $stats['capacidad_perfiles'] }} max.</small></div>
                <div class="metric"><span>Ocupados</span><b>{{ $stats['ocupados'] }}</b><small>Activos segun Excel</small></div>
                <div class="metric"><span>Disponibles</span><b>{{ $stats['disponibles'] }}</b><small>Libres segun Excel</small></div>
                <div class="metric"><span>Ultima lectura</span><b style="font-size: clamp(20px, 3vw, 28px);">{{ $lastSync?->format('d/m H:i') ?? 'Nunca' }}</b><small>Manual</small></div>
            </section>

            <section class="quick">
                <article class="quick-card">
                    <div><h2>Excel</h2><p>Configura URL, hoja, columnas y rangos activos.</p></div>
                    <a class="btn" href="{{ route('admin.excel-import-ranges.index') }}">Configurar</a>
                </article>
                <article class="quick-card">
                    <div><h2>Tutoriales</h2><p>Sube imagen, video o pasos visibles para clientes.</p></div>
                    <a class="btn" href="{{ route('admin.tutoriales.index') }}">Editar</a>
                </article>
                <article class="quick-card">
                    <div><h2>Plataformas</h2><p>Administra catalogo, precios, orden e imagenes.</p></div>
                    <a class="btn" href="{{ route('admin.plataformas.index') }}">Gestionar</a>
                </article>
                <article class="quick-card">
                    <div><h2>Inicio publico</h2><p>Abre la pantalla principal tal como la ve el cliente.</p></div>
                    <a class="btn" href="{{ route('home') }}" target="_blank">Ver inicio</a>
                </article>
            </section>

            <section class="section">
                <div class="section-head">
                    <div>
                        <h2>Cuentas y perfiles</h2>
                        <p>Vista agrupada para revisar rapidamente correo, clave, PIN, WhatsApp y vencimiento.</p>
                    </div>
                    <a class="btn" href="{{ route('admin.excel-import-ranges.index') }}">Rangos Excel</a>
                </div>

                @if ($accounts->isEmpty())
                    <div class="empty">No hay cuentas importadas. Presiona Leer Excel ahora o configura los rangos.</div>
                @else
                    <div class="accounts">
                        @foreach ($accounts as $account)
                            <article class="account">
                                <div class="account-top">
                                    <div class="account-email">{{ $account->email ?: 'Cuenta sin correo' }}</div>
                                    <div class="password">{{ $account->password ?: 'Sin contrasena' }}</div>
                                    <div class="tags">
                                        <span class="tag {{ $account->activo ? 'ok' : 'warn' }}">{{ $account->activo ? 'Activa' : 'Inactiva' }}</span>
                                        <span class="tag">{{ $account->perfiles_usados }}/{{ $account->perfiles_total }} perfiles</span>
                                        <span class="tag">{{ $account->source_hoja_excel ?: '-' }} #{{ $account->source_row ?: '-' }}</span>
                                    </div>
                                </div>

                                <div class="profiles">
                                    @foreach ($account->perfiles as $profile)
                                        <div class="profile">
                                            <div class="profile-title">
                                                <b>{{ $profile->nombre_perfil }}</b>
                                                <span class="tag {{ $profile->ocupado ? 'ok' : 'warn' }}">{{ $profile->estado_excel ?: ($profile->ocupado ? 'Activo' : 'Libre') }}</span>
                                            </div>
                                            <div class="profile-data">
                                                <span>PIN: <b>{{ $profile->pin ?: '-' }}</b></span>
                                                <span>WhatsApp: <b>{{ $profile->numero ?: '-' }}</b></span>
                                                <span>Vendedor: <b>{{ $profile->vendedor ?: '-' }}</b></span>
                                                <span>Costo: <b>{{ $profile->costo !== null ? 'S/ '.$profile->costo : '-' }}</b></span>
                                                <span>Inicio: <b>{{ $profile->fecha_inicio?->format('d/m/Y') ?: '-' }}</b></span>
                                                <span>Vence: <b>{{ $profile->fecha_fin?->format('d/m/Y') ?: '-' }}</b></span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="section">
                <div class="section-head">
                    <div>
                        <h2>Detalle de perfiles</h2>
                        <p>Tabla para busqueda y revision fina despues de leer Excel.</p>
                    </div>
                </div>

                @if ($profiles->isEmpty())
                    <div class="empty">No hay perfiles importados todavia.</div>
                @else
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Perfil</th>
                                    <th>PIN</th>
                                    <th>Numero</th>
                                    <th>Vendedor</th>
                                    <th>Costo</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Estado</th>
                                    <th>Correo</th>
                                    <th>Contrasena</th>
                                    <th>Hoja/Fila</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($profiles as $profile)
                                    <tr>
                                        <td><b>{{ $profile->nombre_perfil }}</b></td>
                                        <td>{{ $profile->pin ?: '-' }}</td>
                                        <td>{{ $profile->numero ?: '-' }}</td>
                                        <td>{{ $profile->vendedor ?: '-' }}</td>
                                        <td>{{ $profile->costo !== null ? 'S/ '.$profile->costo : '-' }}</td>
                                        <td>{{ $profile->fecha_inicio?->format('d/m/Y') ?: '-' }}</td>
                                        <td>{{ $profile->fecha_fin?->format('d/m/Y') ?: '-' }}</td>
                                        <td><span class="tag {{ $profile->ocupado ? 'ok' : 'warn' }}">{{ $profile->estado_excel ?: ($profile->ocupado ? 'Activo' : 'Disponible') }}</span></td>
                                        <td>{{ $profile->cuenta?->email ?: '-' }}</td>
                                        <td>{{ $profile->cuenta?->password ?: '-' }}</td>
                                        <td>{{ $profile->source_hoja_excel ?: '-' }} #{{ $profile->source_row ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="section">
                <div class="section-head">
                    <div>
                        <h2>Rangos activos</h2>
                        <p>Filas y hoja que se leen cuando presionas Leer Excel ahora.</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Plataforma</th>
                                <th>Hoja</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Estado</th>
                                <th>Ultima lectura</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ranges as $range)
                                <tr>
                                    <td>{{ $range->plataforma }}</td>
                                    <td>{{ $range->hoja_excel }}</td>
                                    <td>{{ $range->fila_inicio }}</td>
                                    <td>{{ $range->fila_fin }}</td>
                                    <td><span class="tag {{ $range->activo ? 'ok' : 'warn' }}">{{ $range->activo ? 'Activo' : 'Inactivo' }}</span></td>
                                    <td>{{ $range->ultimo_sync_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No hay rangos configurados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
