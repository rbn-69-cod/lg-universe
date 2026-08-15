<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class SecureBotLinkController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            $targetUrl = Crypt::decryptString((string) $request->query('payload'));
        } catch (DecryptException) {
            abort(404);
        }

        abort_unless(filter_var($targetUrl, FILTER_VALIDATE_URL), 404);

        return view('secure-bot-link', [
            'targetUrl' => $targetUrl,
            'host' => parse_url($targetUrl, PHP_URL_HOST) ?: 'link externo',
        ]);
    }
}
