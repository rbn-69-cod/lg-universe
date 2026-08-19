<?php

use App\Models\Cuenta;
use App\Models\EmailPedido;
use App\Models\Perfil;
use App\Models\Producto;

it('returns public netcode tutorials', function () {
    $this->getJson('/api/v1/netcode/tutorials')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'hogar' => ['title', 'steps', 'media_url', 'media_type'],
                'temporal' => ['title', 'steps', 'media_url', 'media_type'],
            ],
        ]);
});

it('searches extracted netcode email values through the versioned api', function () {
    EmailPedido::query()->create([
        'destinatario_original' => 'cliente@example.com',
        'asunto' => 'Actualizar tu hogar con Netflix',
        'remitente' => 'Netflix',
        'fecha_recibido' => now(),
        'cuerpo_html' => 'Actualiza tu hogar',
        'datos_extraidos' => 'https://netflix.com/account/update-primary-location',
        'fecha_procesado_db' => now(),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'email' => 'cliente@example.com',
        'subject' => 'hogar',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('found', true)
        ->assertJsonPath('valor_extraido', 'https://netflix.com/account/update-primary-location')
        ->assertJsonPath('tipo', 'link')
        ->assertJsonPath('type', 'link')
        ->assertJsonPath('email', 'cliente@example.com');
});

it('finds a 4 digit login code by processed time even when received time is older', function () {
    config()->set('imap.retention_minutes', 7);

    EmailPedido::query()->create([
        'destinatario_original' => 'abngelco120@igruben.lat',
        'asunto' => 'Netflix: Tu codigo de inicio de sesion',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subHour(),
        'cuerpo_html' => 'Ingresa este codigo para iniciar sesion en Netflix.',
        'datos_extraidos' => json_encode(['1234']),
        'fecha_procesado_db' => now()->subMinutes(2),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'email' => 'ABNGELCO120@IGRUBEN.LAT',
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('found', true)
        ->assertJsonPath('valor_extraido', '1234')
        ->assertJsonPath('tipo', 'codigo')
        ->assertJsonPath('valid_for_minutes', 7)
        ->assertJsonPath('seconds_remaining', fn (int $seconds) => $seconds > 0 && $seconds <= 420)
        ->assertJsonStructure(['processed_at', 'expires_at']);
});

it('does not expose account or table bot links through the public netcode search', function () {
    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium-public-bot',
        'precio' => 15,
        'tipo' => 'perfil',
        'perfiles_por_cuenta' => 5,
        'duracion_dias' => 30,
        'activo' => true,
    ]);

    Cuenta::query()->create([
        'producto_id' => $product->id,
        'email' => 'principal@example.com',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 1,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 10,
        'bot_preferencia' => 'personalizado',
        'bot_hogar_url' => 'https://example.com/bot-propio',
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'email' => 'principal@example.com',
        'subject' => 'hogar',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'not_found')
        ->assertJsonPath('found', false);
});

it('returns the latest valid email only for the selected account', function () {
    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium-latest-account',
        'precio' => 15,
        'tipo' => 'perfil',
        'perfiles_por_cuenta' => 5,
        'duracion_dias' => 30,
        'activo' => true,
    ]);

    $accountA = Cuenta::query()->create([
        'producto_id' => $product->id,
        'email' => 'cuenta-a@example.com',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 2,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 3,
    ]);

    $accountB = Cuenta::query()->create([
        'producto_id' => $product->id,
        'email' => 'cuenta-b@example.com',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 1,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 4,
    ]);

    Perfil::query()->create([
        'cuenta_id' => $accountA->id,
        'nombre_perfil' => 'ALFA',
        'pin' => '1111',
        'numero' => '900000001',
        'cliente_acceso_usuario' => 'alfa-a',
        'vendedor' => 'IGARLOS',
        'costo' => 15,
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addMonth(),
        'estado_excel' => 'Activo',
        'ocupado' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 3,
    ]);

    Perfil::query()->create([
        'cuenta_id' => $accountA->id,
        'nombre_perfil' => 'BETA',
        'pin' => '2222',
        'numero' => '900000002',
        'cliente_acceso_usuario' => 'beta-a',
        'vendedor' => 'IGARLOS',
        'costo' => 15,
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addMonth(),
        'estado_excel' => 'Activo',
        'ocupado' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 4,
    ]);

    Perfil::query()->create([
        'cuenta_id' => $accountB->id,
        'nombre_perfil' => 'GAMMA',
        'pin' => '3333',
        'numero' => '900000003',
        'cliente_acceso_usuario' => 'gamma-b',
        'vendedor' => 'IGARLOS',
        'costo' => 15,
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addMonth(),
        'estado_excel' => 'Activo',
        'ocupado' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 5,
    ]);

    EmailPedido::query()->create([
        'destinatario_original' => 'cuenta-a@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(6),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['1111']),
        'fecha_procesado_db' => now()->subMinutes(6),
    ]);

    EmailPedido::query()->create([
        'destinatario_original' => 'cuenta-b@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(3),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['9999']),
        'fecha_procesado_db' => now()->subMinutes(3),
    ]);

    EmailPedido::query()->create([
        'destinatario_original' => 'cuenta-a@example.com',
        'asunto' => 'Netflix: inicia sesion',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinute(),
        'cuerpo_html' => 'Abre este link',
        'datos_extraidos' => json_encode(['https://www.netflix.com/login/token-demo']),
        'fecha_procesado_db' => now()->subMinute(),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'account_id' => $accountA->id,
        'email' => 'otro@example.com',
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('found', true)
        ->assertJsonPath('account_id', $accountA->id)
        ->assertJsonPath('email', 'cuenta-a@example.com')
        ->assertJsonPath('type', 'link')
        ->assertJsonPath('value', 'https://www.netflix.com/login/token-demo');
});

it('returns only the latest temporal email for the client view even when newer emails of another type exist', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::create(2026, 8, 19, 10, 0, 0, 'America/Lima'));

    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium-latest-temporal-only',
        'precio' => 15,
        'tipo' => 'perfil',
        'perfiles_por_cuenta' => 5,
        'duracion_dias' => 30,
        'activo' => true,
    ]);

    $account = Cuenta::query()->create([
        'producto_id' => $product->id,
        'email' => 'cliente-temporal@example.com',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 1,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 9,
    ]);

    EmailPedido::query()->create([
        'destinatario_original' => 'cliente-temporal@example.com',
        'asunto' => 'Netflix temporal viejo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(5),
        'cuerpo_html' => 'Temporal viejo',
        'datos_extraidos' => json_encode([
            'type' => 'temporary_access',
            'action_url' => 'https://www.netflix.com/account/travel/code?token=old-temporal',
        ]),
        'fecha_procesado_db' => now()->subMinutes(5),
    ]);

    EmailPedido::query()->create([
        'destinatario_original' => 'cliente-temporal@example.com',
        'asunto' => 'Netflix temporal nuevo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(2),
        'cuerpo_html' => 'Temporal nuevo',
        'datos_extraidos' => json_encode([
            'type' => 'temporary_access',
            'action_url' => 'https://www.netflix.com/account/travel/code?token=new-temporal',
        ]),
        'fecha_procesado_db' => now()->subMinutes(2),
    ]);

    EmailPedido::query()->create([
        'destinatario_original' => 'cliente-temporal@example.com',
        'asunto' => 'Netflix hogar mas nuevo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinute(),
        'cuerpo_html' => 'Hogar nuevo',
        'datos_extraidos' => json_encode([
            'type' => 'household_update',
            'action_url' => 'https://www.netflix.com/account/travel/verify?token=new-household',
        ]),
        'fecha_procesado_db' => now()->subMinute(),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'account_id' => $account->id,
        'subject' => 'temporal',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('found', true)
        ->assertJsonPath('type', 'link')
        ->assertJsonPath('value', 'https://www.netflix.com/account/travel/code?token=new-temporal');

    Carbon\Carbon::setTestNow();
});

it('does not return another account email when searching a selected account', function () {
    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium-other-account',
        'precio' => 15,
        'tipo' => 'perfil',
        'perfiles_por_cuenta' => 5,
        'duracion_dias' => 30,
        'activo' => true,
    ]);

    $accountA = Cuenta::query()->create([
        'producto_id' => $product->id,
        'email' => 'cuenta-a@example.com',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 1,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 1,
    ]);

    $accountB = Cuenta::query()->create([
        'producto_id' => $product->id,
        'email' => 'cuenta-b@example.com',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 1,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 2,
    ]);

    Perfil::query()->create([
        'cuenta_id' => $accountA->id,
        'nombre_perfil' => 'ALFA',
        'pin' => '1111',
        'numero' => '900000001',
        'cliente_acceso_usuario' => 'alfa-a',
        'vendedor' => 'IGARLOS',
        'costo' => 15,
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addMonth(),
        'estado_excel' => 'Activo',
        'ocupado' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 1,
    ]);

    Perfil::query()->create([
        'cuenta_id' => $accountB->id,
        'nombre_perfil' => 'BETA',
        'pin' => '2222',
        'numero' => '900000002',
        'cliente_acceso_usuario' => 'beta-b',
        'vendedor' => 'IGARLOS',
        'costo' => 15,
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addMonth(),
        'estado_excel' => 'Activo',
        'ocupado' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 2,
    ]);

    EmailPedido::query()->create([
        'destinatario_original' => 'cuenta-b@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(2),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['5555']),
        'fecha_procesado_db' => now()->subMinutes(2),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'account_id' => $accountA->id,
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'not_found')
        ->assertJsonPath('found', false);
});

it('does not return expired results older than seven minutes', function () {
    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium-expired',
        'precio' => 15,
        'tipo' => 'perfil',
        'perfiles_por_cuenta' => 5,
        'duracion_dias' => 30,
        'activo' => true,
    ]);

    $account = Cuenta::query()->create([
        'producto_id' => $product->id,
        'email' => 'expira@example.com',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 1,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 8,
    ]);

    Perfil::query()->create([
        'cuenta_id' => $account->id,
        'nombre_perfil' => 'ALFA',
        'pin' => '1111',
        'numero' => '900000001',
        'cliente_acceso_usuario' => 'alfa-expira',
        'vendedor' => 'IGARLOS',
        'costo' => 15,
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addMonth(),
        'estado_excel' => 'Activo',
        'ocupado' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 8,
    ]);

    EmailPedido::query()->create([
        'destinatario_original' => 'expira@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(8),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['1234']),
        'fecha_procesado_db' => now()->subMinutes(8),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'account_id' => $account->id,
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'not_found')
        ->assertJsonPath('found', false);
});

it('returns results created six minutes ago inside the seven minute window', function () {
    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium-valid-window',
        'precio' => 15,
        'tipo' => 'perfil',
        'perfiles_por_cuenta' => 5,
        'duracion_dias' => 30,
        'activo' => true,
    ]);

    $account = Cuenta::query()->create([
        'producto_id' => $product->id,
        'email' => 'vigente@example.com',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 1,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 9,
    ]);

    Perfil::query()->create([
        'cuenta_id' => $account->id,
        'nombre_perfil' => 'ALFA',
        'pin' => '1111',
        'numero' => '900000001',
        'cliente_acceso_usuario' => 'alfa-vigente',
        'vendedor' => 'IGARLOS',
        'costo' => 15,
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addMonth(),
        'estado_excel' => 'Activo',
        'ocupado' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 9,
    ]);

    EmailPedido::query()->create([
        'destinatario_original' => 'vigente@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subMinutes(6),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['4321']),
        'fecha_procesado_db' => now()->subMinutes(6),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'account_id' => $account->id,
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('found', true)
        ->assertJsonPath('type', 'codigo')
        ->assertJsonPath('value', '4321');
});

it('returns 420 seconds remaining when a code was just processed', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::create(2026, 8, 16, 5, 20, 0, 'America/Lima'));

    EmailPedido::query()->create([
        'destinatario_original' => 'ahora@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now(),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['3002']),
        'fecha_procesado_db' => now(),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'email' => 'ahora@example.com',
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('seconds_remaining', 420)
        ->assertJsonPath('processed_at', '2026-08-16 05:20:00')
        ->assertJsonPath('expires_at', '2026-08-16 05:27:00')
        ->assertJsonPath('validity_source', 'processed_at');

    Carbon\Carbon::setTestNow();
});

it('returns 360 seconds remaining when a code was processed sixty seconds ago', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::create(2026, 8, 16, 5, 21, 0, 'America/Lima'));

    EmailPedido::query()->create([
        'destinatario_original' => 'sesenta@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subSeconds(60),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['3002']),
        'fecha_procesado_db' => now()->subSeconds(60),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'email' => 'sesenta@example.com',
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('seconds_remaining', 360);

    Carbon\Carbon::setTestNow();
});

it('returns 60 seconds remaining when a code was processed three hundred sixty seconds ago', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::create(2026, 8, 16, 5, 26, 0, 'America/Lima'));

    EmailPedido::query()->create([
        'destinatario_original' => 'trescientos@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subSeconds(360),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['3002']),
        'fecha_procesado_db' => now()->subSeconds(360),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'email' => 'trescientos@example.com',
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('seconds_remaining', 60);

    Carbon\Carbon::setTestNow();
});

it('does not return a code processed exactly four hundred twenty seconds ago', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::create(2026, 8, 16, 5, 27, 0, 'America/Lima'));

    EmailPedido::query()->create([
        'destinatario_original' => 'limite@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subSeconds(420),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['3002']),
        'fecha_procesado_db' => now()->subSeconds(420),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'email' => 'limite@example.com',
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'not_found')
        ->assertJsonPath('found', false);

    Carbon\Carbon::setTestNow();
});

it('does not return a code processed more than four hundred twenty seconds ago', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::create(2026, 8, 16, 5, 27, 1, 'America/Lima'));

    EmailPedido::query()->create([
        'destinatario_original' => 'expirado421@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subSeconds(421),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['3002']),
        'fecha_procesado_db' => now()->subSeconds(421),
    ]);

    $this->postJson('/api/v1/netcode/buscar-email', [
        'email' => 'expirado421@example.com',
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'not_found')
        ->assertJsonPath('found', false);

    Carbon\Carbon::setTestNow();
});

it('never returns seconds remaining above four hundred twenty or below zero', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::create(2026, 8, 16, 5, 21, 0, 'America/Lima'));

    EmailPedido::query()->create([
        'destinatario_original' => 'acotado@example.com',
        'asunto' => 'Netflix: tu codigo',
        'remitente' => 'Netflix',
        'fecha_recibido' => now()->subSeconds(60),
        'cuerpo_html' => 'Ingresa este codigo',
        'datos_extraidos' => json_encode(['3002']),
        'fecha_procesado_db' => now()->subSeconds(60),
    ]);

    $response = $this->postJson('/api/v1/netcode/buscar-email', [
        'email' => 'acotado@example.com',
        'subject' => 'acceso4',
    ])
        ->assertOk()
        ->json();

    expect($response['seconds_remaining'])->toBeGreaterThanOrEqual(0);
    expect($response['seconds_remaining'])->toBeLessThanOrEqual(420);

    Carbon\Carbon::setTestNow();
});

it('validates netflix profile access through the versioned api', function () {
    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium-validate-pin',
        'precio' => 15,
        'tipo' => 'perfil',
        'perfiles_por_cuenta' => 5,
        'duracion_dias' => 30,
        'activo' => true,
    ]);

    $account = Cuenta::query()->create([
        'producto_id' => $product->id,
        'email' => 'netflix@example.com',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 1,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 3,
    ]);

    Perfil::query()->create([
        'cuenta_id' => $account->id,
        'nombre_perfil' => 'RUBEN',
        'pin' => '1234',
        'numero' => '987654321',
        'cliente_acceso_usuario' => 'cliente-ruben',
        'vendedor' => 'IGARLOS',
        'costo' => 15,
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addMonth(),
        'estado_excel' => 'Activo',
        'ocupado' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 3,
    ]);

    $this->postJson('/api/v1/netcode/netflix-validar', [
        'step' => 'pin',
        'numero' => '987654321',
        'nombre_perfil' => 'Ruben',
        'pin' => '1234',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('cuenta.id', $account->id)
        ->assertJsonPath('cuenta.email', 'netflix@example.com')
        ->assertJsonPath('perfil.id', fn ($id) => is_int($id) || ctype_digit((string) $id))
        ->assertJsonPath('perfil.nombre', 'RUBEN');
});

it('validates netflix profile access directly with the imported client access user', function () {
    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium-client-access',
        'precio' => 15,
        'tipo' => 'perfil',
        'perfiles_por_cuenta' => 5,
        'duracion_dias' => 30,
        'activo' => true,
    ]);

    $account = Cuenta::query()->create([
        'producto_id' => $product->id,
        'email' => 'directo@example.com',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 1,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 10,
        'bot_preferencia' => 'personalizado',
        'bot_acceso4_url' => 'https://example.com/bot-codigo-netflix',
    ]);

    Perfil::query()->create([
        'cuenta_id' => $account->id,
        'nombre_perfil' => 'MANDI',
        'pin' => '4321',
        'numero' => '900111222',
        'cliente_acceso_usuario' => 'TOKEN-X-123',
        'vendedor' => 'IGARLOS',
        'costo' => 15,
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addMonth(),
        'estado_excel' => 'Activo',
        'ocupado' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 10,
    ]);

    $this->postJson('/api/v1/netcode/netflix-validar', [
        'step' => 'cliente_acceso',
        'cliente_acceso_usuario' => 'token-x-123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Ingresa el PIN del perfil.');

    $this->postJson('/api/v1/netcode/netflix-validar', [
        'step' => 'cliente_acceso',
        'cliente_acceso_usuario' => 'token-x-123',
        'pin' => '1111',
    ])
        ->assertNotFound()
        ->assertJsonPath('message', 'El PIN no coincide con ese acceso.');

    $response = $this->postJson('/api/v1/netcode/netflix-validar', [
        'step' => 'cliente_acceso',
        'cliente_acceso_usuario' => 'token-x-123',
        'pin' => '4321',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('step', 'cliente_acceso')
        ->assertJsonPath('cuenta.id', $account->id)
        ->assertJsonPath('cuenta.email', 'directo@example.com')
        ->assertJsonPath('perfil.nombre', 'MANDI')
        ->assertJsonPath('perfil.cliente_acceso_usuario', 'TOKEN-X-123')
        ->assertJsonPath('cuenta.bot_preferencia', 'personalizado')
        ->assertJsonPath('cuenta.bot_acceso4_url', null)
        ->assertJsonPath('cuenta.bot_acceso4_masked_url', fn (string $url) => str_contains($url, '/netcode/bot/acceso4?payload='));

    $maskedUrl = $response->json('cuenta.bot_acceso4_masked_url');
    $this->get($maskedUrl)
        ->assertOk()
        ->assertSee('bot exclusivo de codigo de acceso')
        ->assertSee('example.com');
});
