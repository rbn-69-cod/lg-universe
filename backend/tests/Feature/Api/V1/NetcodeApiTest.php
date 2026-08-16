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
        ->assertJsonPath('valor_extraido', 'https://netflix.com/account/update-primary-location')
        ->assertJsonPath('tipo', 'link');
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
        ->assertJsonPath('valor_extraido', '1234')
        ->assertJsonPath('tipo', 'codigo')
        ->assertJsonPath('valid_for_minutes', 7)
        ->assertJsonPath('seconds_remaining', fn (int $seconds) => $seconds > 0 && $seconds <= 420)
        ->assertJsonStructure(['processed_at', 'expires_at']);
});

it('does not expose account or table bot links through the public netcode search', function () {
    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium',
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
        ->assertJsonPath('status', 'not_found');
});

it('validates netflix profile access through the versioned api', function () {
    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium',
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
        ->assertJsonPath('cuenta.email', 'netflix@example.com')
        ->assertJsonPath('perfil.nombre', 'RUBEN');
});

it('validates netflix profile access directly with the imported client access user', function () {
    $product = Producto::query()->create([
        'nombre' => 'Netflix Premium',
        'slug' => 'netflix-premium',
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
