<?php

use App\Models\EmailPedido;
use App\Models\User;
use App\Services\Dashboard\DashboardData;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

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
    expect($imap['client_visible_count'])->toBe(2);
    expect($imap['dashboard_only_count'])->toBe(1);
    expect($imap['client_visible_items'])->toHaveCount(2);
    expect($imap['dashboard_only_items'])->toHaveCount(1);
    expect($imap['recent_items'][0]['message_id'])->toBe('<household@example.com>');
    expect($imap['recent_items'][1]['message_id'])->toBe('<unknown@example.com>');
    expect($imap['recent_items'][2]['message_id'])->toBe('<login@example.com>');
    expect($imap['recent_items'][0]['action_url'])->toBe('https://www.netflix.com/account/travel/verify?token=abc&lang=es');
    expect($imap['recent_items'][0]['client_visible'])->toBeTrue();
    expect($imap['recent_items'][0]['seconds_remaining'])->toBe(360);
    expect($imap['recent_items'][1]['client_visible'])->toBeFalse();
    expect($imap['recent_items'][1]['dashboard_state'])->toBe('solo_dashboard_revision');
    expect($imap['recent_items'][1]['tipo'])->toBe('unknown');
    expect($imap['recent_items'][1]['extraction_status'])->toBe('failed');
    expect($imap['recent_items'][1]['found_links'])->toBe(['https://www.netflix.com/review?token=zzz']);
    expect($imap['recent_items'][2]['codigo'])->toBe('6108');
    expect($imap['recent_items'][2]['seconds_remaining'])->toBe(240);

    Carbon::setTestNow();
});

it('allows admin to clear emails that are no longer visible to the client but remain in dashboard history', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 18, 18, 45, 0, 'America/Lima'));

    $this->withoutMiddleware(ValidateCsrfToken::class);
    $this->actingAs(User::factory()->admin()->create());

    EmailPedido::query()->create([
        'message_id' => '<expired@example.com>',
        'imap_uid' => '201',
        'destinatario_original' => 'cliente@example.com',
        'asunto' => 'Netflix expiro',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(12),
        'cuerpo_html' => '<p>7777</p>',
        'html_body_original' => '<p>7777</p>',
        'text_body_original' => '7777',
        'datos_extraidos' => json_encode([
            'platform' => 'Netflix',
            'type' => 'login_code',
            'code' => '7777',
            'action_url' => null,
            'value' => '7777',
            'extraction_status' => 'success',
            'found_links' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'extraction_status' => 'success',
        'fecha_procesado_db' => now()->subMinutes(12),
    ]);

    EmailPedido::query()->create([
        'message_id' => '<fresh@example.com>',
        'imap_uid' => '202',
        'destinatario_original' => 'cliente@example.com',
        'asunto' => 'Netflix vigente',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(2),
        'cuerpo_html' => '<p>8888</p>',
        'html_body_original' => '<p>8888</p>',
        'text_body_original' => '8888',
        'datos_extraidos' => json_encode([
            'platform' => 'Netflix',
            'type' => 'login_code',
            'code' => '8888',
            'action_url' => null,
            'value' => '8888',
            'extraction_status' => 'success',
            'found_links' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'extraction_status' => 'success',
        'fecha_procesado_db' => now()->subMinutes(2),
    ]);

    $this->postJson('/api/v1/dashboard/imap-history/clear')
        ->assertOk()
        ->assertJsonPath('deleted', 1)
        ->assertJsonPath('data.imap.client_visible_count', 1)
        ->assertJsonPath('data.imap.dashboard_only_count', 0);

    expect(EmailPedido::query()->where('message_id', '<expired@example.com>')->exists())->toBeFalse();
    expect(EmailPedido::query()->where('message_id', '<fresh@example.com>')->exists())->toBeTrue();

    Carbon::setTestNow();
});

it('allows admin to delete one email from dashboard history', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 18, 18, 45, 0, 'America/Lima'));

    $this->withoutMiddleware(ValidateCsrfToken::class);
    $this->actingAs(User::factory()->admin()->create());

    $email = EmailPedido::query()->create([
        'message_id' => '<delete-me@example.com>',
        'imap_uid' => '301',
        'destinatario_original' => 'cliente@example.com',
        'asunto' => 'Netflix borrar',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(4),
        'cuerpo_html' => '<p>1234</p>',
        'html_body_original' => '<p>1234</p>',
        'text_body_original' => '1234',
        'datos_extraidos' => json_encode([
            'platform' => 'Netflix',
            'type' => 'login_code',
            'code' => '1234',
            'action_url' => null,
            'value' => '1234',
            'extraction_status' => 'success',
            'found_links' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'extraction_status' => 'success',
        'fecha_procesado_db' => now()->subMinutes(4),
    ]);

    $this->deleteJson("/api/v1/dashboard/imap-history/{$email->id}")
        ->assertOk()
        ->assertJsonPath('data.imap.stored_recent_count', 0);

    expect(EmailPedido::query()->whereKey($email->id)->exists())->toBeFalse();

    Carbon::setTestNow();
});
