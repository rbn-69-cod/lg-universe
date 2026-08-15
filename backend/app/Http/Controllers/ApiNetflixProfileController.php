<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use Illuminate\Http\Request;
use Throwable;

class ApiNetflixProfileController extends Controller
{
    public function validateAccess(Request $request)
    {
        try {
            $data = $request->validate([
                'step' => ['nullable', 'in:whatsapp,nombre,pin,full,cliente_acceso'],
                'numero' => ['nullable', 'string', 'max:40'],
                'nombre_perfil' => ['nullable', 'string', 'max:120'],
                'pin' => ['nullable', 'string', 'max:20'],
                'cliente_acceso_usuario' => ['nullable', 'string', 'max:255'],
            ]);

            $step = $data['step'] ?? 'full';
            $numero = $this->digits((string) ($data['numero'] ?? ''));
            $nombre = $this->normalizeName((string) ($data['nombre_perfil'] ?? ''));
            $pin = $this->normalizePin((string) ($data['pin'] ?? ''));

            if ($step === 'cliente_acceso') {
                $profile = $this->profileByClientAccess((string) ($data['cliente_acceso_usuario'] ?? ''));

                if (! $profile || ! $profile->cuenta) {
                    return response()->json([
                        'status' => 'not_found',
                        'step' => 'cliente_acceso',
                        'message' => 'No encontramos ese acceso de cliente.',
                    ], 404);
                }

                if ($pin === '') {
                    return response()->json([
                        'status' => 'not_found',
                        'step' => 'cliente_acceso',
                        'message' => 'Ingresa el PIN del perfil.',
                    ], 422);
                }

                if ($this->normalizePin((string) $profile->pin) !== $pin) {
                    return response()->json([
                        'status' => 'not_found',
                        'step' => 'cliente_acceso',
                        'message' => 'El PIN no coincide con ese acceso.',
                    ], 404);
                }

                return $this->accountResponse($profile, 'cliente_acceso');
            }

            $profiles = $this->profilesByWhatsapp($numero);

            if ($profiles->isEmpty()) {
                return response()->json([
                    'status' => 'not_found',
                    'step' => 'whatsapp',
                    'message' => 'No encontramos ese WhatsApp en Netflix Premium.',
                ], 404);
            }

            if ($step === 'whatsapp') {
                return response()->json([
                    'status' => 'success',
                    'step' => 'whatsapp',
                    'message' => 'WhatsApp validado.',
                    'profiles' => $profiles->map(fn (Perfil $profile) => [
                        'nombre' => $profile->nombre_perfil,
                        'pin' => $profile->pin,
                        'vence' => $profile->fecha_fin?->format('d/m/Y'),
                        'estado' => $profile->estado_excel,
                        'fila_excel' => $profile->source_row,
                    ])->values(),
                ]);
            }

            if ($nombre === '') {
                return response()->json([
                    'status' => 'not_found',
                    'step' => 'nombre',
                    'message' => 'Ingresa el nombre del perfil.',
                ], 422);
            }

            $profiles = $profiles->filter(fn (Perfil $profile) => $this->normalizeName((string) $profile->nombre_perfil) === $nombre);

            if ($profiles->isEmpty()) {
                return response()->json([
                    'status' => 'not_found',
                    'step' => 'nombre',
                    'message' => 'El nombre no coincide con ese WhatsApp.',
                ], 404);
            }

            if ($step === 'nombre') {
                return response()->json([
                    'status' => 'success',
                    'step' => 'nombre',
                    'message' => 'Nombre validado.',
                ]);
            }

            if ($pin === '') {
                return response()->json([
                    'status' => 'not_found',
                    'step' => 'pin',
                    'message' => 'Ingresa el PIN.',
                ], 422);
            }

            $profile = $profiles->first(fn (Perfil $profile) => $this->normalizePin((string) $profile->pin) === $pin);

            if (! $profile || ! $profile->cuenta) {
                return response()->json([
                    'status' => 'not_found',
                    'step' => 'pin',
                    'message' => 'El PIN no coincide con ese perfil.',
                ], 404);
            }

            return $this->accountResponse($profile, 'pin');
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo validar la cuenta.',
            ], 500);
        }
    }

    private function profilesByWhatsapp(string $numero)
    {
        return Perfil::query()
            ->with('cuenta.producto')
            ->where('source_platforma', 'Netflix Premium')
            ->get()
            ->filter(fn (Perfil $profile) => $this->digits((string) $profile->numero) === $numero)
            ->values();
    }

    private function profileByClientAccess(string $access): ?Perfil
    {
        $access = $this->normalizeAccess($access);

        if ($access === '') {
            return null;
        }

        return Perfil::query()
            ->with('cuenta.producto')
            ->where('source_platforma', 'Netflix Premium')
            ->whereRaw('LOWER(cliente_acceso_usuario) = ?', [$access])
            ->first();
    }

    private function accountResponse(Perfil $profile, string $step)
    {
        return response()->json([
            'status' => 'success',
            'step' => $step,
            'message' => 'Cuenta validada.',
            'perfil' => [
                'nombre' => $profile->nombre_perfil,
                'pin' => $profile->pin,
                'numero' => $profile->numero,
                'vendedor' => $profile->vendedor,
                'costo' => $profile->costo,
                'fecha_inicio' => $profile->fecha_inicio?->format('d/m/Y'),
                'fecha_fin' => $profile->fecha_fin?->format('d/m/Y'),
                'vence' => $profile->fecha_fin?->format('d/m/Y'),
                'estado' => $profile->estado_excel,
                'ocupado' => $profile->ocupado,
                'hoja_excel' => $profile->source_hoja_excel,
                'fila_excel' => $profile->source_row,
                'cliente_acceso_usuario' => $profile->cliente_acceso_usuario,
            ],
            'cuenta' => [
                'email' => $profile->cuenta->email,
                'password' => $profile->cuenta->password,
                'producto' => $profile->cuenta->producto?->nombre,
                'perfiles_total' => $profile->cuenta->perfiles_total,
                'perfiles_usados' => $profile->cuenta->perfiles_usados,
                'activo' => $profile->cuenta->activo,
                'hoja_excel' => $profile->cuenta->source_hoja_excel,
                'fila_excel' => $profile->cuenta->source_row,
                'cliente_acceso_usuario' => $profile->cuenta->cliente_acceso_usuario,
                'bot_preferencia' => $profile->cuenta->bot_preferencia ?: 'principal',
                'bot_hogar_url' => $profile->cuenta->bot_hogar_url,
                'bot_temporal_url' => $profile->cuenta->bot_temporal_url,
                'bot_acceso4_url' => $profile->cuenta->bot_acceso4_url,
            ],
        ]);
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function normalizeName(string $value): string
    {
        $value = trim($value);
        $value = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = mb_strtoupper($value, 'UTF-8');
        $value = preg_replace('/[^A-Z0-9]+/', '', $value) ?? '';

        return $value;
    }

    private function normalizePin(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits === '' ? '' : str_pad(substr($digits, -4), 4, '0', STR_PAD_LEFT);
    }

    private function normalizeAccess(string $value): string
    {
        return mb_strtolower(trim($value), 'UTF-8');
    }
}
