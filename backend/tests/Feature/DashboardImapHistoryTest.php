<?php

use App\Models\EmailPedido;
use App\Services\Dashboard\DashboardData;
use Carbon\Carbon;

it('returns all netflix emails from the last twenty four hours ordered newest first for dashboard history', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 18, 18, 45, 0, 'America/Lima'));

    EmailPedido::query()->create([
        'message_id' => '<old@example.com>',
        'imap_uid' => '101',
        'destinatario_original' => 'cliente@example.com',
        'asunto' => 'Netflix antiguo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subDay()->subMinute(),
        'cuerpo_html' => '<p>Fuera de historial</p>',
        'html_body_original' => '<p>Fuera de historial</p>',
        'text_body_original' => 'Fuera de historial',
        'datos_extraidos' => json_encode([
            'platform' => 'Netflix',
            'type' => 'login_code',
            'code' => '0000',
            'action_url' => null,
            'value' => '0000',
            'extraction_status' => 'success',
            'found_links' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'extraction_status' => 'success',
        'fecha_procesado_db' => now()->subDay()->subMinute(),
    ]);

    EmailPedido::query()->create([
        'message_id' => '<login@example.com>',
        'imap_uid' => '102',
        'destinatario_original' => 'cliente@example.com',
        'asunto' => 'Netflix login',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(3),
        'raw_email' => "Message-ID: <login@example.com>\r\n",
        'html_body_original' => '<p>6108</p>',
        'text_body_original' => '6108',
        'cuerpo_html' => '<p>6108</p>',
        'datos_extraidos' => json_encode([
            'platform' => 'Netflix',
            'type' => 'login_code',
            'code' => '6108',
            'action_url' => null,
            'value' => '6108',
            'extraction_status' => 'success',
            'found_links' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'extraction_status' => 'success',
        'fecha_procesado_db' => now()->subMinutes(3),
    ]);

    EmailPedido::query()->create([
        'message_id' => '<household@example.com>',
        'imap_uid' => '103',
        'destinatario_original' => 'cliente@example.com',
        'asunto' => 'Netflix hogar',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinute(),
        'raw_email' => "Message-ID: <household@example.com>\r\n",
        'html_body_original' => '<a href="https://www.netflix.com/account/travel/verify?token=abc&lang=es">Si, la envie yo</a>',
        'text_body_original' => 'Si, la envie yo',
        'cuerpo_html' => '<a href="https://www.netflix.com/account/travel/verify?token=abc&lang=es">Si, la envie yo</a>',
        'datos_extraidos' => json_encode([
            'platform' => 'Netflix',
            'type' => 'household_update',
            'code' => null,
            'action_url' => 'https://www.netflix.com/account/travel/verify?token=abc&lang=es',
            'value' => 'https://www.netflix.com/account/travel/verify?token=abc&lang=es',
            'extraction_status' => 'success',
            'found_links' => ['https://www.netflix.com/account/travel/verify?token=abc&lang=es'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'extraction_status' => 'success',
        'fecha_procesado_db' => now()->subMinute(),
    ]);

    EmailPedido::query()->create([
        'message_id' => '<unknown@example.com>',
        'imap_uid' => '104',
        'destinatario_original' => 'cliente@example.com',
        'asunto' => 'Netflix sin detectar',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(2),
        'raw_email' => "Message-ID: <unknown@example.com>\r\n",
        'html_body_original' => '<a href="https://www.netflix.com/review?token=zzz">Revisar</a>',
        'text_body_original' => 'Revisar',
        'cuerpo_html' => '<a href="https://www.netflix.com/review?token=zzz">Revisar</a>',
        'datos_extraidos' => json_encode([
            'platform' => 'Netflix',
            'type' => 'unknown',
            'code' => null,
            'action_url' => null,
            'value' => null,
            'extraction_status' => 'failed',
            'found_links' => ['https://www.netflix.com/review?token=zzz'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'extraction_status' => 'failed',
        'fecha_procesado_db' => now()->subMinutes(2),
    ]);

    $imap = app(DashboardData::class)->api()['imap'];

    expect($imap['stored_recent_count'])->toBe(3);
    expect($imap['recent_items'])->toHaveCount(3);
    expect($imap['recent_items'][0]['message_id'])->toBe('<household@example.com>');
    expect($imap['recent_items'][1]['message_id'])->toBe('<unknown@example.com>');
    expect($imap['recent_items'][2]['message_id'])->toBe('<login@example.com>');
    expect($imap['recent_items'][0]['action_url'])->toBe('https://www.netflix.com/account/travel/verify?token=abc&lang=es');
    expect($imap['recent_items'][1]['tipo'])->toBe('unknown');
    expect($imap['recent_items'][1]['extraction_status'])->toBe('failed');
    expect($imap['recent_items'][1]['found_links'])->toBe(['https://www.netflix.com/review?token=zzz']);
    expect($imap['recent_items'][2]['codigo'])->toBe('6108');

    Carbon::setTestNow();
});
