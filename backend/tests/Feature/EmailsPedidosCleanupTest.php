<?php

use App\Console\Commands\ProcesarEmailsPedidos;
use App\Models\EmailPedido;
use Carbon\Carbon;

it('deletes expired emails_pedidos rows at seven minutes or more', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 16, 0, 27, 0, 'America/Lima'));
    config()->set('imap.processed_table', 'emails_pedidos');
    config()->set('imap.retention_minutes', 7);

    $expiredByProcessedAt = EmailPedido::query()->create([
        'destinatario_original' => 'expired-processed@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(10),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['1111']),
        'fecha_procesado_db' => now()->subMinutes(7),
    ]);

    $expiredLaterRow = EmailPedido::query()->create([
        'destinatario_original' => 'expired-later@example.com',
        'asunto' => 'Netflix: tu link',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(8),
        'cuerpo_html' => 'Abre este link',
        'datos_extraidos' => json_encode(['https://www.netflix.com/token-expired']),
        'fecha_procesado_db' => now()->subMinutes(7)->subSecond(),
    ]);

    $command = app(ProcesarEmailsPedidos::class);
    $deletedRows = $command->cleanupExpiredEmails('emails_pedidos');

    expect($deletedRows)->toBe(2);
    expect(EmailPedido::query()->whereKey($expiredByProcessedAt->id)->exists())->toBeFalse();
    expect(EmailPedido::query()->whereKey($expiredLaterRow->id)->exists())->toBeFalse();

    Carbon::setTestNow();
});

it('does not delete rows that are still inside the seven minute window', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 16, 0, 26, 59, 'America/Lima'));
    config()->set('imap.processed_table', 'emails_pedidos');
    config()->set('imap.retention_minutes', 7);

    $activeProcessed = EmailPedido::query()->create([
        'destinatario_original' => 'active-processed@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(6)->subSeconds(59),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['2222']),
        'fecha_procesado_db' => now()->subMinutes(6)->subSeconds(59),
    ]);

    $activeLaterRow = EmailPedido::query()->create([
        'destinatario_original' => 'active-later@example.com',
        'asunto' => 'Netflix: tu link',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(6)->subSeconds(59),
        'cuerpo_html' => 'Abre este link',
        'datos_extraidos' => json_encode(['https://www.netflix.com/token-active']),
        'fecha_procesado_db' => now()->subMinutes(6)->subSeconds(58),
    ]);

    $command = app(ProcesarEmailsPedidos::class);
    $deletedRows = $command->cleanupExpiredEmails('emails_pedidos');

    expect($deletedRows)->toBe(0);
    expect(EmailPedido::query()->whereKey($activeProcessed->id)->exists())->toBeTrue();
    expect(EmailPedido::query()->whereKey($activeLaterRow->id)->exists())->toBeTrue();

    Carbon::setTestNow();
});

it('cleanup is idempotent when executed twice', function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 16, 0, 27, 1, 'America/Lima'));
    config()->set('imap.processed_table', 'emails_pedidos');
    config()->set('imap.retention_minutes', 7);

    EmailPedido::query()->create([
        'destinatario_original' => 'expired-twice@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(8),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['3333']),
        'fecha_procesado_db' => now()->subMinutes(8),
    ]);

    $command = app(ProcesarEmailsPedidos::class);

    expect($command->cleanupExpiredEmails('emails_pedidos'))->toBe(1);
    expect($command->cleanupExpiredEmails('emails_pedidos'))->toBe(0);
    expect(EmailPedido::query()->count())->toBe(0);

    Carbon::setTestNow();
});
