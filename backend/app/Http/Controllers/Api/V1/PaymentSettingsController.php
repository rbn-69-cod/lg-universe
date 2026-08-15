<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\PaymentSettings;
use Illuminate\Http\JsonResponse;

class PaymentSettingsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = PaymentSettings::get();

        $settings['methods'] = array_values(array_filter(
            $settings['methods'],
            fn (array $method) => (bool) ($method['active'] ?? true)
        ));

        return response()->json([
            'data' => $settings,
        ]);
    }
}
