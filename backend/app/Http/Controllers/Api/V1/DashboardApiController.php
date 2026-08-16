<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cuenta;
use App\Models\ExcelImportRange;
use App\Models\Perfil;
use App\Models\Plataforma;
use App\Models\User;
use App\Services\Dashboard\DashboardData;
use App\Services\Excel\NetflixPremiumExcelImporter;
use App\Support\PaymentSettings;
use App\Support\TutorialContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DashboardApiController extends Controller
{
    public function __construct(
        private readonly DashboardData $dashboardData,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesion cerrada.',
        ]);
    }

    public function storeRange(Request $request): JsonResponse
    {
        $range = ExcelImportRange::query()->create($this->rangeData($request));

        return response()->json([
            'message' => 'Tabla Excel creada.',
            'range' => $range,
            'data' => $this->dashboardData->api(),
        ], 201);
    }

    public function updateRange(Request $request, ExcelImportRange $excelImportRange): JsonResponse
    {
        $excelImportRange->update($this->rangeData($request, $excelImportRange->id));

        return response()->json([
            'message' => 'Tabla Excel actualizada.',
            'range' => $excelImportRange->refresh(),
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function destroyRange(ExcelImportRange $excelImportRange): JsonResponse
    {
        $excelImportRange->delete();

        return response()->json([
            'message' => 'Tabla Excel eliminada.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function updateAccountBotLinks(Request $request, Cuenta $cuenta): JsonResponse
    {
        $data = $request->validate([
            'cliente_acceso_usuario' => ['nullable', 'string', 'max:255'],
            'bot_preferencia' => ['required', 'in:principal,personalizado'],
            'bot_hogar_url' => ['nullable', 'url'],
            'bot_temporal_url' => ['nullable', 'url'],
            'bot_acceso4_url' => ['nullable', 'url'],
        ]);

        $cuenta->update([
            'cliente_acceso_usuario' => $data['cliente_acceso_usuario'] ?? null,
            'bot_preferencia' => $data['bot_preferencia'],
            'bot_hogar_url' => $data['bot_hogar_url'] ?? null,
            'bot_temporal_url' => $data['bot_temporal_url'] ?? null,
            'bot_acceso4_url' => $data['bot_acceso4_url'] ?? null,
        ]);

        return response()->json([
            'message' => 'Links de bot actualizados.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function updatePaymentSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'seller.business_name' => ['required', 'string', 'max:120'],
            'seller.display_name' => ['required', 'string', 'max:120'],
            'seller.contact_name' => ['nullable', 'string', 'max:120'],
            'seller.whatsapp' => ['required', 'string', 'max:30'],
            'seller.phone' => ['nullable', 'string', 'max:30'],
            'seller.email' => ['nullable', 'email', 'max:160'],
            'seller.address' => ['nullable', 'string', 'max:255'],
            'seller.support_text' => ['nullable', 'string', 'max:500'],
            'instructions' => ['required', 'string', 'max:1000'],
            'methods' => ['required', 'array', 'min:1', 'max:4'],
            'methods.*.id' => ['required', 'integer', 'min:1', 'max:4'],
            'methods.*.title' => ['required', 'string', 'max:80'],
            'methods.*.subtitle' => ['nullable', 'string', 'max:80'],
            'methods.*.badge' => ['nullable', 'string', 'max:40'],
            'methods.*.recommended' => ['boolean'],
            'methods.*.qr_src' => ['required', 'string', 'max:500'],
            'methods.*.qr_fallback' => ['nullable', 'url', 'max:700'],
            'methods.*.account_name' => ['required', 'string', 'max:120'],
            'methods.*.account_phone' => ['required', 'string', 'max:40'],
            'methods.*.copy_phone' => ['required', 'string', 'max:40'],
            'methods.*.whatsapp' => ['required', 'string', 'max:30'],
            'methods.*.active' => ['boolean'],
        ]);

        PaymentSettings::save($data);

        return response()->json([
            'message' => 'Datos de pago actualizados.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function uploadPaymentQr(Request $request, int $method): JsonResponse
    {
        abort_unless($method >= 1 && $method <= 4, 404);

        $data = $request->validate([
            'qr' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
        ], [
            'qr.max' => 'El QR no puede pesar mas de 10 MB.',
            'qr.mimes' => 'Sube una imagen JPG, PNG o WEBP.',
        ]);

        $settings = PaymentSettings::get();
        $index = collect($settings['methods'])->search(fn (array $item) => (int) $item['id'] === $method);

        if ($index === false) {
            throw ValidationException::withMessages([
                'qr' => 'Metodo de pago no encontrado.',
            ]);
        }

        $currentPath = $this->paymentQrPathFromUrl((string) ($settings['methods'][$index]['qr_src'] ?? ''));
        if ($currentPath) {
            Storage::disk('public')->delete($currentPath);
        }

        $file = $data['qr'];
        $path = $file->storeAs(
            'payment-qrs',
            'method-'.$method.'-'.Str::random(10).'.'.$file->getClientOriginalExtension(),
            'public'
        );

        $settings['methods'][$index]['qr_src'] = route('payment-media', ['path' => $path]);
        PaymentSettings::save($settings);

        return response()->json([
            'message' => 'QR actualizado.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function syncExcel(Request $request, NetflixPremiumExcelImporter $importer): JsonResponse
    {
        $data = $request->validate([
            'range_id' => ['nullable', 'integer', 'exists:excel_import_ranges,id'],
            'plataforma' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $stats = $importer->sync($data['range_id'] ?? null, $data['plataforma'] ?? null);

            return response()->json([
                'message' => 'Sincronizacion completada.',
                'stats' => $stats,
                'data' => $this->dashboardData->api(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function runImap(): JsonResponse
    {
        Artisan::call('emails:procesar-pedidos');

        return response()->json([
            'message' => 'IMAP ejecutado.',
            'output' => trim(Artisan::output()),
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function testImapConnection(): JsonResponse
    {
        if (! function_exists('imap_open')) {
            return response()->json([
                'message' => 'La extension PHP IMAP no esta habilitada.',
            ], 422);
        }

        $mailbox = (string) config('imap.mailbox');
        $username = (string) config('imap.username');
        $password = (string) config('imap.password');
        $criteria = trim((string) config('imap.search_criteria', 'UNSEEN')) ?: 'UNSEEN';

        if ($username === '' || $password === '') {
            return response()->json([
                'message' => 'IMAP no configurado: faltan credenciales.',
            ], 422);
        }

        $inbox = @imap_open($mailbox, $username, $password);

        if (! $inbox) {
            return response()->json([
                'message' => 'No se pudo conectar al buzon IMAP.',
                'detail' => imap_last_error(),
            ], 422);
        }

        $uids = imap_search($inbox, $criteria, SE_UID);
        $uids = is_array($uids) ? array_values($uids) : [];
        rsort($uids);

        $latest = [];
        foreach (array_slice($uids, 0, 5) as $uid) {
            $messageNo = imap_msgno($inbox, (int) $uid);
            $header = $messageNo ? @imap_headerinfo($inbox, $messageNo) : null;

            if (! $header) {
                continue;
            }

            $latest[] = [
                'uid' => (int) $uid,
                'subject' => str_replace('_', ' ', mb_decode_mimeheader($header->subject ?? 'Sin asunto')),
                'from' => isset($header->from[0]->mailbox, $header->from[0]->host)
                    ? $header->from[0]->mailbox.'@'.$header->from[0]->host
                    : null,
                'date' => isset($header->udate) ? date('Y-m-d H:i:s', $header->udate) : null,
            ];
        }

        imap_close($inbox);

        return response()->json([
            'message' => 'Conexion IMAP correcta.',
            'criteria' => $criteria,
            'found' => count($uids),
            'latest' => $latest,
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function updateImapSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'imap_server' => ['required', 'string', 'max:255'],
            'imap_user' => ['required', 'string', 'max:255'],
            'imap_password' => ['nullable', 'string', 'max:255'],
            'imap_search_criteria' => ['required', 'string', 'max:80'],
            'imap_mark_seen' => ['boolean'],
            'emails_max_minutes' => ['required', 'integer', 'min:1', 'max:7'],
            'cron_token' => ['nullable', 'string', 'min:4', 'max:120'],
        ]);

        $updates = [
            'IMAP_SERVER' => $data['imap_server'],
            'IMAP_USER' => $data['imap_user'],
            'IMAP_SEARCH_CRITERIA' => $data['imap_search_criteria'],
            'IMAP_MARK_SEEN' => $request->boolean('imap_mark_seen') ? 'true' : 'false',
            'EMAILS_MAX_MINUTES' => (string) $data['emails_max_minutes'],
        ];

        if (trim((string) ($data['imap_password'] ?? '')) !== '') {
            $updates['IMAP_PASSWORD'] = $data['imap_password'];
        }

        if (trim((string) ($data['cron_token'] ?? '')) !== '') {
            $updates['CRON_TOKEN'] = $data['cron_token'];
        }

        $this->updateEnv($updates);
        Artisan::call('config:clear');

        return response()->json([
            'message' => 'Configuracion IMAP/Cron actualizada.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function updateTutorial(Request $request, string $key): JsonResponse
    {
        abort_unless(array_key_exists($key, TutorialContent::KEYS), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'steps' => ['nullable', 'string', 'max:3000'],
            'media' => ['nullable', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm'],
        ], [
            'media.max' => 'El archivo no puede pesar mas de 50 MB.',
            'media.mimes' => 'Sube imagen JPG/PNG/WEBP/GIF o video MP4/MOV/WEBM.',
        ]);

        TutorialContent::save(
            $key,
            $data['title'],
            preg_split('/\R+/', (string) ($data['steps'] ?? '')) ?: [],
            $request->file('media')
        );

        return response()->json([
            'message' => 'Tutorial actualizado.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function removeTutorialMedia(string $key): JsonResponse
    {
        abort_unless(array_key_exists($key, TutorialContent::KEYS), 404);

        TutorialContent::removeMedia($key);

        return response()->json([
            'message' => 'Archivo del tutorial eliminado.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function storeCatalogPlatform(Request $request): JsonResponse
    {
        $data = $this->catalogData($request);
        $data['orden'] = ((int) Plataforma::query()->max('orden')) + 1;

        Plataforma::query()->create($data);

        return response()->json([
            'message' => 'Plataforma creada.',
            'data' => $this->dashboardData->api(),
        ], 201);
    }

    public function updateCatalogPlatform(Request $request, Plataforma $plataforma): JsonResponse
    {
        $plataforma->update($this->catalogData($request));

        return response()->json([
            'message' => 'Plataforma actualizada.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function destroyCatalogPlatform(Plataforma $plataforma): JsonResponse
    {
        $plataforma->delete();

        return response()->json([
            'message' => 'Plataforma eliminada.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function moveCatalogPlatform(Plataforma $plataforma, string $direction): JsonResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $other = Plataforma::query()
            ->where('orden', $direction === 'up' ? '<' : '>', $plataforma->orden)
            ->orderBy('orden', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($other) {
            $currentOrder = $plataforma->orden;
            $plataforma->update(['orden' => $other->orden]);
            $other->update(['orden' => $currentOrder]);
        }

        return response()->json([
            'message' => 'Orden actualizado.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function storeAdmin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        User::query()->create($data + [
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Admin creado.',
            'data' => $this->dashboardData->api(),
        ], 201);
    }

    public function updateAdmin(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        if (trim((string) ($data['password'] ?? '')) === '') {
            unset($data['password']);
        }

        $user->update($data);

        if (! $user->isAdmin()) {
            $user->forceFill(['role' => User::ROLE_ADMIN])->save();
        }

        return response()->json([
            'message' => 'Admin actualizado.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function destroyAdmin(User $user): JsonResponse
    {
        if ((int) auth()->id() === (int) $user->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propio usuario conectado.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Admin eliminado.',
            'data' => $this->dashboardData->api(),
        ]);
    }

    public function clearImportedData(Request $request): JsonResponse
    {
        $request->validate([
            'confirmation' => ['required', 'in:LIMPIAR'],
        ]);

        $profileIds = Perfil::query()->whereNotNull('source_platforma')->pluck('id');
        $accountIds = Cuenta::query()->whereNotNull('source_platforma')->pluck('id');

        Perfil::query()->whereKey($profileIds)->delete();
        Cuenta::query()->whereKey($accountIds)->delete();

        return response()->json([
            'message' => 'Cuentas y perfiles importados eliminados.',
            'deleted' => [
                'perfiles' => $profileIds->count(),
                'cuentas' => $accountIds->count(),
            ],
            'data' => $this->dashboardData->api(),
        ]);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function updateEnv(array $values): void
    {
        $path = base_path('.env');
        $content = File::exists($path) ? File::get($path) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->envValue($value);
            if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $content)) {
                $content = preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $content) ?? $content;
            } else {
                $content .= PHP_EOL.$line;
            }
        }

        File::put($path, trim($content).PHP_EOL);
    }

    private function envValue(string $value): string
    {
        if ($value === '' || preg_match('/[\s#"\'=]/', $value)) {
            return '"'.str_replace('"', '\"', $value).'"';
        }

        return $value;
    }

    private function paymentQrPathFromUrl(string $url): ?string
    {
        $prefix = url('payment-media/');

        if (! str_starts_with($url, $prefix)) {
            return null;
        }

        return ltrim(substr($url, strlen($prefix)), '/');
    }

    private function rangeData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'plataforma' => ['required', 'string', 'max:255'],
            'nombre_tabla' => ['nullable', 'string', 'max:255'],
            'producto_slug' => ['nullable', 'string', 'max:255'],
            'archivo_url' => ['required', 'url'],
            'bot_codigo_url' => ['nullable', 'url'],
            'bot_soporte_url' => ['nullable', 'url'],
            'hoja_excel' => ['required', 'string', 'max:255'],
            'fila_inicio' => ['required', 'integer', 'min:1'],
            'fila_fin' => ['required', 'integer', 'gte:fila_inicio'],
            'columna_perfil' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_pin' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_numero' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_vendedor_igarlos' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_vendedor_nikol' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_costo' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_fecha_inicio' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_fecha_fin' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_estado' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_correo' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_password' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'columna_cliente_acceso_usuario' => ['required', 'regex:/^[A-Za-z]{1,3}$/'],
            'activo' => ['boolean'],
        ]);

        $data['activo'] = $request->boolean('activo', true);
        $data['producto_slug'] = trim((string) ($data['producto_slug'] ?? '')) !== ''
            ? Str::slug((string) $data['producto_slug'])
            : null;

        foreach ($this->columnFields() as $field) {
            $data[$field] = strtoupper(trim((string) $data[$field]));
        }

        $overlap = ExcelImportRange::query()
            ->where('hoja_excel', $data['hoja_excel'])
            ->where('archivo_url', $data['archivo_url'])
            ->where('activo', true)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('fila_inicio', '<=', $data['fila_fin'])
            ->where('fila_fin', '>=', $data['fila_inicio'])
            ->first();

        if (($data['activo'] ?? false) && $overlap) {
            throw ValidationException::withMessages([
                'fila_inicio' => "El rango {$data['fila_inicio']}-{$data['fila_fin']} se superpone con {$overlap->plataforma} {$overlap->fila_inicio}-{$overlap->fila_fin} en la misma hoja.",
            ]);
        }

        return $data;
    }

    private function catalogData(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'imagen' => ['nullable', 'string', 'max:500'],
            'precio' => ['required', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'features' => ['nullable', 'string', 'max:3000'],
            'activacion' => ['nullable', 'string', 'max:2000'],
            'terminos' => ['nullable', 'string', 'max:2000'],
            'activo' => ['boolean'],
        ]);

        $data['features'] = array_values(array_filter(array_map(
            fn (string $feature) => trim($feature),
            preg_split('/\r\n|\r|\n/', (string) ($data['features'] ?? '')) ?: []
        )));
        $data['activo'] = $request->boolean('activo', true);

        return $data;
    }

    private function columnFields(): array
    {
        return [
            'columna_perfil',
            'columna_pin',
            'columna_numero',
            'columna_vendedor_igarlos',
            'columna_vendedor_nikol',
            'columna_costo',
            'columna_fecha_inicio',
            'columna_fecha_fin',
            'columna_estado',
            'columna_correo',
            'columna_password',
            'columna_cliente_acceso_usuario',
        ];
    }
}
