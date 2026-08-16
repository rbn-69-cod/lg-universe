<?php

namespace App\Http\Controllers;

use App\Models\EmailPedido;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class ApiBuscarEmailController extends Controller
{
    public function buscar(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'subject' => ['required', 'in:temporal,hogar,acceso4'],
            ]);

            $emailDestinatario = mb_strtolower(trim($request->email));
            $tipoSolicitud = trim($request->subject);
            $retentionMinutes = min(7, max(1, (int) config('imap.retention_minutes', 7)));
            $validFrom = now()->subMinutes($retentionMinutes);

            $query = EmailPedido::whereRaw('LOWER(destinatario_original) = ?', [$emailDestinatario])
                ->where(function ($q) use ($validFrom) {
                    $q->where('fecha_procesado_db', '>=', $validFrom)
                        ->orWhere(function ($fallback) use ($validFrom) {
                            $fallback->whereNull('fecha_procesado_db')
                                ->where('fecha_recibido', '>=', $validFrom);
                        });
                });

            $query->where(function ($q) use ($tipoSolicitud) {
                if ($tipoSolicitud === 'acceso4') {
                    $this->applyKeywordFilters($q, array_merge(
                        $this->configuredKeywords('temporal'),
                        ['codigo', 'inicio de sesion', 'tu codigo de inicio', 'acceso', 'ingresa este codigo para iniciar sesion', 'codigo vence en 15 minutos']
                    ));
                    $q->orWhere('datos_extraidos', 'REGEXP', '^[0-9]{4}$');

                    return;
                }

                if ($tipoSolicitud === 'temporal') {
                    $this->applyKeywordFilters($q, array_merge(
                        $this->configuredKeywords('temporal'),
                        ['temporal', 'code', 'codigo', 'acceso']
                    ));
                    $q->orWhere('datos_extraidos', 'LIKE', 'http%')
                        ->orWhere('datos_extraidos', 'REGEXP', '^[0-9]{4,8}$');

                    return;
                }

                $this->applyKeywordFilters($q, array_merge(
                    $this->configuredKeywords('hogar'),
                    ['hogar', 'actualizar', 'primary', 'household']
                ));
                $q->orWhere('datos_extraidos', 'LIKE', '%update-primary-location%')
                    ->orWhere('datos_extraidos', 'LIKE', '%travel/verify%');
            });

            $emailDataModel = $query->orderBy('fecha_procesado_db', 'desc')
                ->orderBy('fecha_recibido', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if (! $emailDataModel) {
                return $this->safeJsonResponse([
                    'status' => 'not_found',
                    'message' => 'No se encontro ningun correo reciente coincidente.',
                ]);
            }

            $valorFinal = $this->extractStoredValue($emailDataModel->datos_extraidos, $tipoSolicitud);

            if (! is_string($valorFinal) || trim($valorFinal) === '') {
                return $this->safeJsonResponse([
                    'status' => 'not_found',
                    'message' => 'El correo existe, pero no tiene codigo o enlace extraido.',
                    'debug_id' => $emailDataModel->id,
                ]);
            }

            if ($tipoSolicitud === 'acceso4' && ! preg_match('/^\d{4}$/', $valorFinal)) {
                return $this->safeJsonResponse([
                    'status' => 'not_found',
                    'message' => 'Se encontro correo, pero no contiene un codigo de 4 digitos.',
                    'debug_id' => $emailDataModel->id,
                ]);
            }

            $fechaFormat = null;
            try {
                $fechaFormat = $emailDataModel->fecha_recibido
                    ? Carbon::parse($emailDataModel->fecha_recibido)->format('Y-m-d H:i:s')
                    : null;
            } catch (Throwable) {
                $fechaFormat = null;
            }

            return $this->safeJsonResponse([
                'status' => 'success',
                'message' => 'Dato encontrado.',
                'valor_extraido' => $valorFinal,
                'tipo' => preg_match('/^\d{4}$/', $valorFinal) ? 'codigo' : 'link',
                'fecha' => $fechaFormat,
                'debug_id' => $emailDataModel->id,
                'asunto_found' => $emailDataModel->asunto,
            ]);
        } catch (Throwable $e) {
            return $this->safeJsonResponse([
                'status' => 'error',
                'message' => 'Error interno buscando el correo.',
            ], 500);
        }
    }

    private function extractStoredValue(?string $raw, string $tipoSolicitud): string
    {
        $raw = trim((string) $raw);
        $decoded = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && count($decoded) > 0) {
            $values = array_values(array_filter(array_map(
                fn ($value) => html_entity_decode(trim((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                $decoded
            )));

            if ($tipoSolicitud === 'acceso4') {
                $match = collect($values)->first(fn (string $value) => preg_match('/^\d{4}$/', $value));

                return is_string($match) ? $match : '';
            }

            if ($tipoSolicitud === 'hogar') {
                $match = collect($values)->first(fn (string $value) => str_starts_with($value, 'http'));

                return is_string($match) ? $match : '';
            }

            $raw = (string) end($values);
        }

        $raw = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw) ?? '';

        return html_entity_decode(trim($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function configuredKeywords(string $type): array
    {
        $raw = (string) config("imap.keywords.{$type}", '');

        return array_values(array_unique(array_filter(array_map(
            fn (string $keyword) => trim($keyword),
            explode(',', $raw)
        ))));
    }

    private function applyKeywordFilters($query, array $keywords): void
    {
        $first = true;

        foreach ($keywords as $keyword) {
            $keyword = trim((string) $keyword);
            if ($keyword === '') {
                continue;
            }

            $method = $first ? 'where' : 'orWhere';
            $query->{$method}('asunto', 'LIKE', "%{$keyword}%")
                ->orWhere('cuerpo_html', 'LIKE', "%{$keyword}%");

            $first = false;
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
