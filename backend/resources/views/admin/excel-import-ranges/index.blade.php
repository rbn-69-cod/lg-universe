<x-layouts.app :title="__('Configuracion Excel')">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-cyan-600 dark:text-cyan-300">
                    Configuracion de importacion Excel
                </p>
                <h1 class="mt-1 text-2xl font-bold text-zinc-950 dark:text-white">
                    Netflix Premium
                </h1>
                <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">
                    Administra la URL del Excel, hoja y rangos activos sin tocar codigo. La sincronizacion solo procesa Netflix Premium y se actualiza manualmente cuando presionas Leer Excel / Sincronizar ahora.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.excel-import-ranges.sync') }}">
                @csrf
                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                    Leer Excel ahora
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3 text-sm text-cyan-900 dark:border-cyan-900 dark:bg-cyan-950 dark:text-cyan-100">
            Cada cambio que hagas en el Excel se vera en el sistema despues de presionar <strong>Leer Excel ahora</strong>. Esta configuracion esta pensada para 15 cuentas y 75 perfiles de Netflix Premium.
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <h2 class="text-lg font-bold text-zinc-950 dark:text-white">Agregar rango</h2>
            </div>

            @php
                $columnDefaults = [
                    'columna_perfil' => ['Perfil', 'F'],
                    'columna_pin' => ['PIN', 'G'],
                    'columna_numero' => ['Numero', 'H'],
                    'columna_vendedor_igarlos' => ['Vendedor IGARLOS', 'I'],
                    'columna_vendedor_nikol' => ['Vendedor NIKOL', 'J'],
                    'columna_costo' => ['Costo', 'K'],
                    'columna_fecha_inicio' => ['Fecha inicio', 'L'],
                    'columna_fecha_fin' => ['Fecha fin', 'M'],
                    'columna_estado' => ['Estado', 'N'],
                    'columna_correo' => ['Correo', 'U'],
                    'columna_password' => ['Contrasena', 'V'],
                    'columna_cliente_acceso_usuario' => ['Acceso cliente', 'X'],
                ];
            @endphp

            <form method="POST" action="{{ route('admin.excel-import-ranges.store') }}" class="grid gap-4 p-5 md:grid-cols-12">
                @csrf

                <label class="md:col-span-5">
                    <span class="mb-1 block text-sm font-semibold text-zinc-700 dark:text-zinc-200">URL del Excel</span>
                    <input
                        type="url"
                        name="archivo_url"
                        value="{{ old('archivo_url', $defaultUrl) }}"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        required
                    >
                </label>

                <label class="md:col-span-3">
                    <span class="mb-1 block text-sm font-semibold text-zinc-700 dark:text-zinc-200">Hoja Excel</span>
                    <input
                        type="text"
                        name="hoja_excel"
                        value="{{ old('hoja_excel', 'NETFLIX PREMUM') }}"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        required
                    >
                </label>

                <label class="md:col-span-1">
                    <span class="mb-1 block text-sm font-semibold text-zinc-700 dark:text-zinc-200">Desde</span>
                    <input type="number" name="fila_inicio" min="1" value="{{ old('fila_inicio', 3) }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" required>
                </label>

                <label class="md:col-span-1">
                    <span class="mb-1 block text-sm font-semibold text-zinc-700 dark:text-zinc-200">Hasta</span>
                    <input type="number" name="fila_fin" min="1" value="{{ old('fila_fin', 77) }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-950 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" required>
                </label>

                <label class="flex items-end gap-2 md:col-span-1">
                    <input type="checkbox" name="activo" value="1" class="mb-3" checked>
                    <span class="mb-2 text-sm font-semibold text-zinc-700 dark:text-zinc-200">Activo</span>
                </label>

                <div class="flex items-end md:col-span-1">
                    <button class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">
                        + Agregar
                    </button>
                </div>

                <div class="md:col-span-12">
                    <div class="mb-2 text-sm font-bold text-zinc-950 dark:text-white">Que columnas leer</div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                        @foreach ($columnDefaults as $field => [$label, $default])
                            <label>
                                <span class="mb-1 block text-xs font-semibold text-zinc-600 dark:text-zinc-300">{{ $label }}</span>
                                <input
                                    name="{{ $field }}"
                                    value="{{ old($field, $default) }}"
                                    maxlength="3"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-bold uppercase text-zinc-950 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                    required
                                >
                            </label>
                        @endforeach
                    </div>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <h2 class="text-lg font-bold text-zinc-950 dark:text-white">Rangos configurados</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 text-left text-xs font-bold uppercase text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">Plataforma</th>
                            <th class="px-4 py-3">Hoja</th>
                            <th class="px-4 py-3">Desde</th>
                            <th class="px-4 py-3">Hasta</th>
                            <th class="px-4 py-3">Columnas que lee</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Ultimo sync</th>
                            <th class="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($ranges as $range)
                            <tr class="align-top">
                                <td class="px-4 py-3 font-semibold text-zinc-950 dark:text-white">
                                    {{ $range->plataforma }}
                                    <div class="mt-1 max-w-xs truncate text-xs font-normal text-zinc-500" title="{{ $range->archivo_url }}">
                                        {{ $range->archivo_url }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <input form="update-range-{{ $range->id }}" name="hoja_excel" value="{{ $range->hoja_excel }}" class="w-32 rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                </td>
                                <td class="px-4 py-3">
                                    <input form="update-range-{{ $range->id }}" type="number" name="fila_inicio" min="1" value="{{ $range->fila_inicio }}" class="w-24 rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                </td>
                                <td class="px-4 py-3">
                                    <input form="update-range-{{ $range->id }}" type="number" name="fila_fin" min="1" value="{{ $range->fila_fin }}" class="w-24 rounded border border-zinc-300 bg-white px-2 py-1 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                </td>
                                <td class="px-4 py-3">
                                    <details class="min-w-80">
                                        <summary class="cursor-pointer text-xs font-bold text-cyan-600 dark:text-cyan-300">
                                            Editar columnas
                                        </summary>
                                        <div class="mt-3 grid grid-cols-2 gap-2">
                                            @foreach ($columnDefaults as $field => [$label, $default])
                                                <label>
                                                    <span class="mb-1 block text-[11px] font-semibold text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
                                                    <input
                                                        form="update-range-{{ $range->id }}"
                                                        name="{{ $field }}"
                                                        value="{{ $range->{$field} ?: $default }}"
                                                        maxlength="3"
                                                        class="w-full rounded border border-zinc-300 bg-white px-2 py-1 text-xs font-bold uppercase dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                                                        required
                                                    >
                                                </label>
                                            @endforeach
                                        </div>
                                    </details>
                                </td>
                                <td class="px-4 py-3">
                                    <label class="inline-flex items-center gap-2">
                                        <input form="update-range-{{ $range->id }}" type="checkbox" name="activo" value="1" @checked($range->activo)>
                                        <span class="{{ $range->activo ? 'text-emerald-600' : 'text-zinc-500' }}">
                                            {{ $range->activo ? 'ACTIVO' : 'INACTIVO' }}
                                        </span>
                                    </label>
                                    @if ($range->ultimo_error)
                                        <div class="mt-1 max-w-xs text-xs text-red-600">{{ $range->ultimo_error }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                                    {{ $range->ultimo_sync_at?->format('Y-m-d H:i') ?? 'Nunca' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <form id="update-range-{{ $range->id }}" method="POST" action="{{ route('admin.excel-import-ranges.update', $range) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="archivo_url" value="{{ $range->archivo_url }}">
                                            <button class="rounded bg-zinc-900 px-3 py-1 text-xs font-bold text-white dark:bg-white dark:text-zinc-900">
                                                Editar
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.excel-import-ranges.toggle', $range) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded bg-amber-500 px-3 py-1 text-xs font-bold text-white">
                                                {{ $range->activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.excel-import-ranges.destroy', $range) }}" onsubmit="return confirm('Eliminar este rango?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded bg-red-600 px-3 py-1 text-xs font-bold text-white">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-zinc-500">
                                    No hay rangos configurados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.app>
