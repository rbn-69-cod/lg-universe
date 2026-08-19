<?php

namespace App\Services\Dashboard;

use App\Models\Cuenta;
use App\Models\EmailPedido;
use App\Models\ExcelImportRange;
use App\Models\Perfil;
use App\Models\Plataforma;
use App\Models\User;
use App\Support\PaymentSettings;
use App\Support\TutorialContent;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class DashboardData
{
    public function blade(): array
    {
        $data = $this->data();

        return [
            'stats' => $data['stats'],
            'accounts' => $data['accounts'],
            'profiles' => $data['profiles'],
            'ranges' => $data['ranges'],
            'lastSync' => $data['lastSync'],
        ];
    }

    public function api(): array
    {
        $data = $this->data();

        return [
            'stats' => $data['stats'],
            'accounts' => $data['accounts']->map(fn (Cuenta $account) => [
                'id' => $account->id,
                'email' => $account->email,
                'password' => $account->password,
                'source_platforma' => $account->source_platforma,
                'perfiles_total' => $account->perfiles_total,
                'perfiles_usados' => $account->perfiles_usados,
                'activo' => $account->activo,
                'source_hoja_excel' => $account->source_hoja_excel,
                'source_row' => $account->source_row,
                'cliente_acceso_usuario' => $account->cliente_acceso_usuario,
                'bot_preferencia' => $account->bot_preferencia ?: 'principal',
                'bot_hogar_url' => $account->bot_hogar_url,
                'bot_temporal_url' => $account->bot_temporal_url,
                'bot_acceso4_url' => $account->bot_acceso4_url,
                'perfiles' => $account->perfiles->map(fn (Perfil $profile) => $this->profile($profile))->values(),
            ])->values(),
            'profiles' => $data['profiles']->map(fn (Perfil $profile) => $this->profile($profile, true))->values(),
            'ranges' => $data['ranges']->map(fn (ExcelImportRange $range) => [
                'id' => $range->id,
                'plataforma' => $range->plataforma,
                'nombre_tabla' => $range->nombre_tabla,
                'producto_slug' => $range->producto_slug,
                'hoja_excel' => $range->hoja_excel,
                'fila_inicio' => $range->fila_inicio,
                'fila_fin' => $range->fila_fin,
                'archivo_url' => $range->archivo_url,
                'bot_codigo_url' => $range->bot_codigo_url,
                'bot_soporte_url' => $range->bot_soporte_url,
                'bot_codigo_masked_url' => $this->secureBotLink($range->bot_codigo_url),
                'bot_soporte_masked_url' => $this->secureBotLink($range->bot_soporte_url),
                'columna_perfil' => $range->columna_perfil,
                'columna_pin' => $range->columna_pin,
                'columna_numero' => $range->columna_numero,
                'columna_vendedor_igarlos' => $range->columna_vendedor_igarlos,
                'columna_vendedor_nikol' => $range->columna_vendedor_nikol,
                'columna_costo' => $range->columna_costo,
                'columna_fecha_inicio' => $range->columna_fecha_inicio,
                'columna_fecha_fin' => $range->columna_fecha_fin,
                'columna_estado' => $range->columna_estado,
                'columna_correo' => $range->columna_correo,
                'columna_password' => $range->columna_password,
                'columna_cliente_acceso_usuario' => $range->columna_cliente_acceso_usuario,
                'activo' => $range->activo,
                'ultimo_sync_at' => $range->ultimo_sync_at?->toIso8601String(),
                'ultimo_error' => $range->ultimo_error,
            ])->values(),
            'catalog' => Plataforma::query()
                ->with(['duraciones' => fn ($query) => $query->orderBy('duracion_meses')])
                ->orderBy('orden')
                ->orderBy('id')
                ->get()
                ->map(fn (Plataforma $platform) => [
                    'id' => $platform->id,
                    'nombre' => $platform->nombre,
                    'imagen' => $platform->imagen,
                    'precio' => (float) $platform->precio,
                    'descripcion' => $platform->descripcion,
                    'features' => $platform->features ?: [],
                    'activacion' => $platform->activacion,
                    'terminos' => $platform->terminos,
                    'activo' => (bool) $platform->activo,
                    'orden' => $platform->orden,
                    'duraciones' => $platform->duraciones
                        ->sortBy('duracion_meses')
                        ->values()
                        ->map(fn ($duration) => [
                            'id' => $duration->id,
                            'duracion_meses' => (int) $duration->duracion_meses,
                            'precio' => (float) $duration->precio,
                            'activo' => (bool) $duration->activo,
                        ])
                        ->all(),
                ])
                ->values(),
            'admins' => User::query()
                ->where('role', User::ROLE_ADMIN)
                ->orderBy('id')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => optional($user->created_at)?->toDateTimeString(),
                ])
                ->values(),
            'imap' => $this->imapStatus(),
            'tutorials' => TutorialContent::all(),
            'tutorial_labels' => TutorialContent::KEYS,
            'payment_settings' => PaymentSettings::get(),
            'last_sync' => $data['lastSync']?->toIso8601String(),
        ];
    }

    private function data(): array
    {
        $importedProfilesQuery = Perfil::query()
            ->with(['cuenta.producto'])
            ->whereNotNull('source_platforma');

        $stats = [
            'cuentas' => Cuenta::query()
                ->whereNotNull('source_platforma')
                ->count(),
            'perfiles' => (clone $importedProfilesQuery)->count(),
            'ocupados' => (clone $importedProfilesQuery)->where('ocupado', true)->count(),
            'disponibles' => (clone $importedProfilesQuery)->where('ocupado', false)->count(),
            'vencidos' => (clone $importedProfilesQuery)->where('estado_excel', 'like', '%vencido%')->count(),
            'capacidad_cuentas' => 15,
            'capacidad_perfiles' => 75,
        ];

        $accounts = Cuenta::query()
            ->with([
                'producto',
                'perfiles' => fn ($query) => $query
                    ->orderBy('source_row')
                    ->orderBy('nombre_perfil'),
            ])
            ->whereNotNull('source_platforma')
            ->orderBy('source_hoja_excel')
            ->orderBy('source_row')
            ->limit(30)
            ->get();

        $profiles = (clone $importedProfilesQuery)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        $ranges = ExcelImportRange::query()
            ->orderByDesc('activo')
            ->orderBy('plataforma')
            ->orderBy('hoja_excel')
            ->orderBy('fila_inicio')
            ->get();

        return [
            'stats' => $stats,
            'accounts' => $accounts,
            'profiles' => $profiles,
            'ranges' => $ranges,
            'lastSync' => $ranges->max('ultimo_sync_at'),
        ];
    }

    private function profile(Perfil $profile, bool $withAccount = false): array
    {
        $data = [
            'id' => $profile->id,
            'nombre_perfil' => $profile->nombre_perfil,
            'pin' => $profile->pin,
            'numero' => $profile->numero,
            'numero_tipo' => $this->phoneType($profile->numero),
            'vendedor' => $profile->vendedor,
            'costo' => $profile->costo,
            'fecha_inicio' => $profile->fecha_inicio?->toDateString(),
            'fecha_fin' => $profile->fecha_fin?->toDateString(),
            'estado_excel' => $profile->estado_excel,
            'ocupado' => $profile->ocupado,
            'source_hoja_excel' => $profile->source_hoja_excel,
            'source_platforma' => $profile->source_platforma,
            'source_row' => $profile->source_row,
            'cliente_acceso_usuario' => $profile->cliente_acceso_usuario,
        ];

        if ($withAccount) {
            $data['cuenta'] = [
                'email' => $profile->cuenta?->email,
                'password' => $profile->cuenta?->password,
                'cliente_acceso_usuario' => $profile->cuenta?->cliente_acceso_usuario,
                'bot_preferencia' => $profile->cuenta?->bot_preferencia ?: 'principal',
                'bot_hogar_url' => $profile->cuenta?->bot_hogar_url,
                'bot_temporal_url' => $profile->cuenta?->bot_temporal_url,
                'bot_acceso4_url' => $profile->cuenta?->bot_acceso4_url,
            ];
        }

        return $data;
    }

    private function secureBotLink(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        return route('bot-links.open', [
            'payload' => Crypt::encryptString($url),
        ]);
    }

    private function phoneType(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return 'sin_numero';
        }

        if (strlen($digits) > 9 && ! str_starts_with($digits, '51')) {
            return 'internacional';
        }

        return 'peru';
    }

    private function imapStatus(): array
    {
        $retention = min(7, max(1, (int) config('imap.retention_minutes', 7)));
        $token = (string) config('services.cron.token', '');
        $cronUrl = route('cron.procesar-emails', ['token' => $token]);
        $historyFrom = now()->subDay();

        return [
            'configured' => (string) config('imap.username', '') !== '' && (string) config('imap.password', '') !== '',
            'mailbox_raw' => (string) config('imap.mailbox', ''),
            'mailbox' => preg_replace('/\{([^}:]+).*$/', '$1', (string) config('imap.mailbox', '')),
            'username' => (string) config('imap.username', ''),
            'search_criteria' => (string) config('imap.search_criteria', 'UNSEEN'),
            'mark_seen' => filter_var(config('imap.mark_seen', true), FILTER_VALIDATE_BOOLEAN),
            'retention_minutes' => $retention,
            'history_window_hours' => 24,
            'cron_url' => $token === '' ? null : $cronUrl,
            'cron_url_masked' => $token === '' ? null : str_replace($token, '***', $cronUrl),
            'cron_token_masked' => $token === '' ? null : substr($token, 0, 3).'***',
            'stored_count' => EmailPedido::query()->count(),
            'stored_recent_count' => EmailPedido::query()
                ->where(function ($query) use ($historyFrom) {
                    $query->whereNotNull('fecha_procesado_db')
                        ->where('fecha_procesado_db', '>=', $historyFrom);
                })
                ->orWhere(function ($query) use ($historyFrom) {
                    $query->whereNull('fecha_procesado_db')
                        ->where('fecha_recibido', '>=', $historyFrom);
                })
                ->count(),
            'last_processed_at' => $this->dateTimeString(EmailPedido::query()->max('fecha_procesado_db')),
            'recent_items' => EmailPedido::query()
                ->where(function ($query) use ($historyFrom) {
                    $query->whereNotNull('fecha_procesado_db')
                        ->where('fecha_procesado_db', '>=', $historyFrom);
                })
                ->orWhere(function ($query) use ($historyFrom) {
                    $query->whereNull('fecha_procesado_db')
                        ->where('fecha_recibido', '>=', $historyFrom);
                })
                ->orderByDesc('fecha_procesado_db')
                ->orderByDesc('fecha_recibido')
                ->orderByDesc('id')
                ->get()
                ->map(fn (EmailPedido $email) => [
                    'id' => $email->id,
                    'message_id' => $email->message_id,
                    'thread_id' => $email->thread_id,
                    'imap_uid' => $email->imap_uid,
                    'destinatario_original' => $email->destinatario_original,
                    'remitente' => $email->remitente,
                    'asunto' => $email->asunto,
                    'tipo' => $this->storedValueType($email->datos_extraidos),
                    'tipo_label' => $this->storedTypeLabel($email->datos_extraidos),
                    'valor_extraido' => $this->storedValue($email->datos_extraidos),
                    'codigo' => $this->storedPayloadValue($email->datos_extraidos, 'code'),
                    'action_url' => $this->storedPayloadValue($email->datos_extraidos, 'action_url'),
                    'extraction_status' => $this->storedPayloadValue($email->datos_extraidos, 'extraction_status') ?: ($email->extraction_status ?: 'failed'),
                    'found_links' => $this->storedLinks($email->datos_extraidos),
                    'fecha_recibido' => optional($email->fecha_recibido)?->toDateTimeString(),
                    'fecha_procesado_db' => optional($email->fecha_procesado_db)?->toDateTimeString(),
                    'raw_email' => $email->raw_email,
                    'html_body_original' => $email->html_body_original ?: $email->cuerpo_html,
                    'text_body_original' => $email->text_body_original,
                ])
                ->values(),
        ];
    }

    private function dateTimeString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function storedValueType(?string $raw): string
    {
        $payload = $this->storedPayload($raw);
        if (is_string($payload['type'] ?? null) && $payload['type'] !== '') {
            return (string) $payload['type'];
        }

        $value = $this->storedValue($raw);

        if (preg_match('/^\d{4}$/', trim($value))) {
            return 'codigo_4';
        }

        if (str_starts_with(trim($value), 'http')) {
            return 'link';
        }

        return trim($value) === '' ? 'sin_dato' : 'codigo';
    }

    private function storedTypeLabel(?string $raw): string
    {
        return match ($this->storedValueType($raw)) {
            'login_code', 'codigo_4', 'codigo' => 'Codigo de inicio de sesion',
            'household_update' => 'Actualizar Hogar',
            'temporary_access' => 'Acceso temporal',
            'link' => 'Link detectado',
            default => 'No se pudo detectar',
        };
    }

    private function storedValue(?string $raw): string
    {
        $payload = $this->storedPayload($raw);
        $structuredValue = trim((string) ($payload['code'] ?? $payload['action_url'] ?? $payload['value'] ?? ''));
        if ($structuredValue !== '') {
            return html_entity_decode($structuredValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $decoded = json_decode((string) $raw, true);
        $values = is_array($decoded) ? $decoded : [$raw];

        $cleanValues = array_values(array_filter(array_map(
            fn ($value) => html_entity_decode(trim((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $values
        )));

        $link = collect($cleanValues)->first(fn (string $value) => str_starts_with($value, 'http'));
        if (is_string($link)) {
            return $link;
        }

        $code = collect($cleanValues)->first(fn (string $value) => preg_match('/^\d{4,8}$/', $value));

        return is_string($code) ? $code : (string) end($cleanValues);
    }

    private function storedPayload(?string $raw): array
    {
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function storedPayloadValue(?string $raw, string $key): ?string
    {
        $payload = $this->storedPayload($raw);
        $value = $payload[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $clean = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $clean !== '' ? $clean : null;
    }

    private function storedLinks(?string $raw): array
    {
        $payload = $this->storedPayload($raw);
        $links = $payload['found_links'] ?? [];

        if (! is_array($links)) {
            return [];
        }

        return collect($links)
            ->map(fn ($value) => trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')))
            ->filter(fn (string $value) => Str::startsWith($value, ['http://', 'https://']))
            ->values()
            ->all();
    }
}
