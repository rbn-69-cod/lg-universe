<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\EmailPedido;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class ApiBuscarEmailController extends Controller
{
    private const CODE_TTL_MINUTES = 7;

    public function buscar(Request $request)
    {
        try {
            $data = $request->validate([
                'email' => ['nullable', 'email', 'required_without:account_id'],
                'account_id' => ['nullable', 'integer', Rule::exists('cuentas', 'id')],
                'subject' => ['required', 'in:temporal,hogar,acceso4'],
            ]);

            $tipoSolicitud = trim((string) $data['subject']);
            $account = null;
            $authorizedRecipients = [];

            if (! empty($data['account_id'])) {
                $account = Cuenta::query()
                    ->with('perfiles:id,cuenta_id,nombre_perfil,cliente_acceso_usuario')
                    ->find((int) $data['account_id']);

                $authorizedRecipients = $this->authorizedRecipientsForAccount($account);
            } elseif (! empty($data['email'])) {
                $authorizedRecipients = [mb_strtolower(trim((string) $data['email']))];
            }

            if ($authorizedRecipients === []) {
                return $this->safeJsonResponse([
                    'status' => 'not_found',
                    'found' => false,
                    'message' => 'La cuenta seleccionada no tiene correos autorizados para NetCode.',
                ]);
            }

            $validFrom = now()->subMinutes(self::CODE_TTL_MINUTES);

            $emailDataModel = EmailPedido::query()
                ->where(function ($query) use ($authorizedRecipients) {
                    foreach ($authorizedRecipients as $index => $recipient) {
                        $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                        $query->{$method}('LOWER(destinatario_original) = ?', [$recipient]);
                    }
                })
                ->where(function ($query) use ($validFrom) {
                    $query->where('fecha_procesado_db', '>=', $validFrom)
                        ->orWhere(function ($fallback) use ($validFrom) {
                            $fallback->whereNull('fecha_procesado_db')
                                ->where('fecha_recibido', '>=', $validFrom);
                        });
                })
                ->orderBy('fecha_procesado_db', 'desc')
                ->orderBy('fecha_recibido', 'desc')
                ->orderBy('id', 'desc')
                ->get()
                ->first(function (EmailPedido $email) use ($tipoSolicitud) {
                    if ($this->extractCandidate($email, $tipoSolicitud) === null) {
                        return false;
                    }

                    return $this->secondsRemaining($email) > 0;
                });

            if (! $emailDataModel) {
                return $this->safeJsonResponse([
                    'status' => 'not_found',
                    'found' => false,
                    'message' => 'No se encontro ningun correo reciente coincidente.',
                ]);
            }

            $candidate = $this->extractCandidate($emailDataModel, $tipoSolicitud);

            if ($candidate === null) {
                return $this->safeJsonResponse([
                    'status' => 'not_found',
                    'found' => false,
                    'message' => 'El correo existe, pero no tiene codigo, link o login valido.',
                    'debug_id' => $emailDataModel->id,
                ]);
            }

            $validityStart = $this->validityStart($emailDataModel);
            $expiresAt = $this->expiresAt($emailDataModel);
            $secondsRemaining = $this->secondsRemaining($emailDataModel);

            if ($secondsRemaining <= 0 || ! $validityStart || ! $expiresAt) {
                return $this->safeJsonResponse([
                    'status' => 'not_found',
                    'found' => false,
                    'message' => 'El correo existe, pero el resultado ya expiro.',
                    'debug_id' => $emailDataModel->id,
                ]);
            }

            $receivedAt = $this->formatDate($emailDataModel->fecha_recibido);
            $processedAt = $emailDataModel->fecha_procesado_db
                ? $this->formatDate($emailDataModel->fecha_procesado_db)
                : null;

            return $this->safeJsonResponse([
                'status' => 'success',
                'found' => true,
                'message' => 'Dato encontrado.',
                'type' => $candidate['type'],
                'value' => $candidate['value'],
                'email' => $emailDataModel->destinatario_original,
                'received_at' => $receivedAt,
                'validity_source' => $emailDataModel->fecha_procesado_db ? 'processed_at' : 'received_at',
                'valor_extraido' => $candidate['value'],
                'tipo' => $candidate['type'],
                'fecha' => $receivedAt,
                'processed_at' => $processedAt,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'seconds_remaining' => $secondsRemaining,
                'valid_for_minutes' => self::CODE_TTL_MINUTES,
                'debug_id' => $emailDataModel->id,
                'asunto_found' => $emailDataModel->asunto,
                'account_id' => $account?->id,
                'authorized_profile_ids' => $account?->perfiles?->pluck('id')->values()->all() ?? [],
            ]);
        } catch (Throwable $e) {
            return $this->safeJsonResponse([
                'status' => 'error',
                'found' => false,
                'message' => 'Error interno buscando el correo.',
            ], 500);
        }
    }

    private function authorizedRecipientsForAccount(?Cuenta $account): array
    {
        if (! $account) {
            return [];
        }

        $emails = collect([
            $account->email,
        ])->map(function (?string $email) {
            $email = mb_strtolower(trim((string) $email));

            return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
        })->filter()->unique()->values();

        return $emails->all();
    }

    private function extractCandidate(EmailPedido $email, string $tipoSolicitud): ?array
    {
        foreach ($this->candidateValues($email) as $value) {
            $candidate = $this->classifyCandidate($value, $tipoSolicitud);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        foreach ($this->fallbackBodyCandidates($email) as $value) {
            $candidate = $this->classifyCandidate($value, $tipoSolicitud);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        return null;
    }

    private function candidateValues(EmailPedido $email): array
    {
        $raw = trim((string) $email->datos_extraidos);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->flattenDecodedValues($decoded);
        }

        return [$this->cleanCandidateValue($raw)];
    }

    private function flattenDecodedValues(mixed $decoded): array
    {
        if (is_array($decoded)) {
            $values = [];

            foreach ($decoded as $value) {
                $values = array_merge($values, $this->flattenDecodedValues($value));
            }

            return array_values(array_filter(array_unique($values)));
        }

        if (is_scalar($decoded)) {
            $value = $this->cleanCandidateValue((string) $decoded);

            return $value !== '' ? [$value] : [];
        }

        return [];
    }

    private function validityStart(EmailPedido $email): ?CarbonImmutable
    {
        try {
            if ($email->fecha_procesado_db) {
                return CarbonImmutable::parse($email->fecha_procesado_db, config('app.timezone'));
            }

            return $email->fecha_recibido
                ? CarbonImmutable::parse($email->fecha_recibido, config('app.timezone'))
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function expiresAt(EmailPedido $email): ?CarbonImmutable
    {
        return $this->validityStart($email)?->addMinutes(self::CODE_TTL_MINUTES);
    }

    private function secondsRemaining(EmailPedido $email): int
    {
        $expiresAt = $this->expiresAt($email);
        if (! $expiresAt) {
            return 0;
        }

        $now = CarbonImmutable::now(config('app.timezone'));
        $secondsRemaining = (int) $now->diffInSeconds($expiresAt, false);

        return max(0, min(self::CODE_TTL_MINUTES * 60, $secondsRemaining));
    }

    private function classifyCandidate(string $value, string $tipoSolicitud): ?array
    {
        $value = $this->cleanCandidateValue($value);

        if ($value === '') {
            return null;
        }

        $isLink = preg_match('/^https?:\/\//i', $value) === 1;
        $isCode4 = preg_match('/^\d{4}$/', $value) === 1;
        $isNumericCode = preg_match('/^\d{4,8}$/', $value) === 1;

        if ($tipoSolicitud === 'hogar') {
            return $isLink
                ? ['type' => 'link', 'value' => $value]
                : null;
        }

        if ($tipoSolicitud === 'temporal') {
            if ($isLink) {
                return ['type' => 'link', 'value' => $value];
            }

            if ($isNumericCode) {
                return ['type' => 'codigo', 'value' => $value];
            }

            return ['type' => 'login', 'value' => $value];
        }

        if ($isCode4) {
            return ['type' => 'codigo', 'value' => $value];
        }

        if ($isLink) {
            return ['type' => 'link', 'value' => $value];
        }

        if (mb_strlen($value) >= 4) {
            return ['type' => 'login', 'value' => $value];
        }

        return null;
    }

    private function fallbackBodyCandidates(EmailPedido $email): array
    {
        $body = html_entity_decode((string) $email->cuerpo_html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', strip_tags($body)) ?? '';
        $candidates = [];

        if (preg_match('/ingresa\s+este\s+codigo[^0-9]*(\d{4,8})/iu', $text, $matches)) {
            $candidates[] = $matches[1];
        }

        if (preg_match('/\b(\d{4})\b/', $text, $matches)) {
            $candidates[] = $matches[1];
        }

        if (preg_match('/(https?:\/\/[^\s"\'<>]+)/i', $body, $matches)) {
            $candidates[] = $matches[1];
        }

        return array_values(array_filter(array_unique(array_map(
            fn (string $value) => $this->cleanCandidateValue($value),
            $candidates
        ))));
    }

    private function cleanCandidateValue(?string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', trim((string) $value)) ?? '';

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function formatDate(mixed $value): ?string
    {
        try {
            return $value
                ? Carbon::parse($value, config('app.timezone'))->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function safeJsonResponse(array $data, int $status = 200)
    {
        $jsonString = json_encode(
            $data,
            JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE
        );

        if ($jsonString === false) {
            $jsonString = '{"status":"error","message":"Error critico de codificacion JSON"}';
        }

        return response($jsonString, $status)
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }
}
