<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IAController extends Controller
{
    public function chat(Request $request)
    {
        $data = $request->validate([
            'mensaje' => ['required', 'string', 'max:500'],
        ]);

        $apiKey = env('OPENROUTER_API_KEY');

        if (! $apiKey) {
            return response()->json([
                'respuesta' => 'Ahora mismo el asistente no esta configurado. Escribeme por WhatsApp y te atiendo rapido.',
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'HTTP-Referer' => request()->getSchemeAndHttpHost(),
                'X-Title' => 'LG Universe Assistant',
                'Content-Type' => 'application/json',
            ])
                ->timeout(25)
                ->retry(1, 800)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => env('OPENROUTER_MODEL', 'openrouter/auto'),
                    'max_tokens' => 120,
                    'temperature' => 0.7,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->systemPrompt(),
                        ],
                        [
                            'role' => 'user',
                            'content' => $data['mensaje'],
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('OpenRouter response error', [
                    'status' => $response->status(),
                ]);

                return $this->fallback();
            }

            $content = data_get($response->json(), 'choices.0.message.content');

            if (! is_string($content) || trim($content) === '') {
                return $this->fallback();
            }

            return response()->json([
                'respuesta' => trim($content),
            ]);
        } catch (\Throwable $e) {
            Log::warning('IA chat failed', ['message' => $e->getMessage()]);

            return $this->fallback();
        }
    }

    private function fallback()
    {
        return response()->json([
            'respuesta' => 'Te ayudo por aqui: Netflix S/15, Disney+ S/10, Max S/7, Prime S/7, IPTV S/12. Que plataforma buscas?',
        ]);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Eres vendedor real de WhatsApp para LG Universe.
Responde solo en espanol, breve, natural y directo.
Maximo 1 a 3 lineas.
No digas que eres IA.
Siempre muestra precios cuando pregunten por plataformas.

Precios:
Netflix S/15
Disney+ S/10
Max S/7
Prime Video S/7
Crunchyroll S/5
IPTV S/12
Spotify S/7
DGO S/28

Pago:
Yape/Plin: 907978279
Captura: 954850003
Activacion aproximada: 5 minutos.
PROMPT;
    }
}
