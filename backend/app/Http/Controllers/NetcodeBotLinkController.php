<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class NetcodeBotLinkController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $payload = json_decode(Crypt::decryptString((string) $request->query('payload')), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            abort(404);
        }

        $account = Cuenta::query()->with('producto')->find((int) ($payload['account_id'] ?? 0));
        $targetUrl = (string) ($payload['url'] ?? '');

        abort_unless($account && $this->isNetflix($account), 404);
        abort_unless(($payload['type'] ?? '') === 'acceso4', 404);
        abort_unless($account->bot_preferencia === 'personalizado', 404);
        abort_unless(hash_equals((string) $account->bot_acceso4_url, $targetUrl), 404);
        abort_unless(filter_var($targetUrl, FILTER_VALIDATE_URL), 404);

        return view('secure-bot-link', [
            'targetUrl' => $targetUrl,
            'host' => parse_url($targetUrl, PHP_URL_HOST) ?: 'link externo',
            'description' => 'Este enlace abre el bot exclusivo de codigo de acceso para esta cuenta Netflix.',
            'backUrl' => url('/netcode/inicio-sesion'),
            'backLabel' => 'Volver a NetCode',
        ]);
    }

    private function isNetflix(Cuenta $account): bool
    {
        $name = mb_strtolower((string) ($account->producto?->nombre ?: $account->source_platforma), 'UTF-8');

        return str_contains($name, 'netflix');
    }
}
