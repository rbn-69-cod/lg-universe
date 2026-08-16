<?php

use App\Models\Cuenta;
use App\Models\Producto;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users are redirected to the angular dashboard', function () {
    $this->actingAs($user = User::factory()->admin()->create());

    $this->get('/dashboard')->assertRedirect('http://localhost:4200/dashboard');
});

test('authenticated users can visit the legacy blade dashboard', function () {
    $this->actingAs($user = User::factory()->admin()->create());

    $this->get('/legacy/dashboard')->assertStatus(200);
});

test('authenticated users can fetch dashboard api data', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'stats' => [
                    'cuentas',
                    'perfiles',
                    'ocupados',
                    'disponibles',
                    'vencidos',
                    'capacidad_cuentas',
                    'capacidad_perfiles',
                ],
                'accounts',
                'profiles',
                'ranges',
                'last_sync',
            ],
        ]);
});

test('authenticated users can create dashboard excel tables', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/dashboard/excel-ranges', [
        'plataforma' => 'Disney',
        'nombre_tabla' => 'Disney principal',
        'producto_slug' => 'disney',
        'archivo_url' => 'https://example.com/disney.xlsx',
        'bot_codigo_url' => 'https://example.com/bot-codigos',
        'bot_soporte_url' => 'https://example.com/bot-soporte',
        'hoja_excel' => 'DISNEY',
        'fila_inicio' => 3,
        'fila_fin' => 20,
        'columna_perfil' => 'F',
        'columna_pin' => 'G',
        'columna_numero' => 'H',
        'columna_vendedor_igarlos' => 'I',
        'columna_vendedor_nikol' => 'J',
        'columna_costo' => 'K',
        'columna_fecha_inicio' => 'L',
        'columna_fecha_fin' => 'M',
        'columna_estado' => 'N',
        'columna_correo' => 'U',
        'columna_password' => 'V',
        'columna_cliente_acceso_usuario' => 'X',
        'activo' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.ranges.0.plataforma', 'Disney')
        ->assertJsonPath('data.ranges.0.bot_codigo_url', 'https://example.com/bot-codigos')
        ->assertJsonPath('data.ranges.0.bot_soporte_url', 'https://example.com/bot-soporte')
        ->assertJsonPath('data.ranges.0.bot_codigo_masked_url', fn ($url) => str_contains($url, '/bot-links/abrir?payload='))
        ->assertJsonPath('data.ranges.0.bot_soporte_masked_url', fn ($url) => str_contains($url, '/bot-links/abrir?payload='));
});

test('active excel tables cannot overlap in the same source sheet even across platforms', function () {
    $this->actingAs(User::factory()->admin()->create());

    $payload = [
        'nombre_tabla' => '',
        'producto_slug' => '',
        'archivo_url' => 'https://example.com/catalog.xlsx',
        'bot_codigo_url' => '',
        'bot_soporte_url' => '',
        'hoja_excel' => 'NETFLIX PREMUM',
        'fila_inicio' => 3,
        'fila_fin' => 77,
        'columna_perfil' => 'F',
        'columna_pin' => 'G',
        'columna_numero' => 'H',
        'columna_vendedor_igarlos' => 'I',
        'columna_vendedor_nikol' => 'J',
        'columna_costo' => 'K',
        'columna_fecha_inicio' => 'L',
        'columna_fecha_fin' => 'M',
        'columna_estado' => 'N',
        'columna_correo' => 'U',
        'columna_password' => 'V',
        'columna_cliente_acceso_usuario' => 'X',
        'activo' => true,
    ];

    $this->postJson('/api/v1/dashboard/excel-ranges', $payload + [
        'plataforma' => 'Netflix Premium',
    ])->assertCreated();

    $this->postJson('/api/v1/dashboard/excel-ranges', array_merge($payload, [
        'plataforma' => 'DISNEY PLUS',
        'nombre_tabla' => 'Disney',
        'fila_inicio' => 3,
        'fila_fin' => 60,
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fila_inicio']);
});

test('authenticated users can update account bot links', function () {
    $this->actingAs(User::factory()->admin()->create());

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
        'email' => 'mandi_kay@batboy.cloud',
        'password' => 'secret',
        'perfiles_total' => 5,
        'perfiles_usados' => 1,
        'activo' => true,
        'source_platforma' => 'Netflix Premium',
        'source_hoja_excel' => 'NETFLIX',
        'source_row' => 3,
    ]);

    $this->putJson("/api/v1/dashboard/accounts/{$account->id}/bot-links", [
        'cliente_acceso_usuario' => 'cliente-mandi',
        'bot_preferencia' => 'principal',
        'bot_hogar_url' => '',
        'bot_temporal_url' => '',
        'bot_acceso4_url' => '',
    ])
        ->assertOk()
        ->assertJsonPath('data.accounts.0.cliente_acceso_usuario', 'cliente-mandi')
        ->assertJsonPath('data.accounts.0.bot_preferencia', 'principal')
        ->assertJsonPath('data.accounts.0.bot_hogar_url', null)
        ->assertJsonPath('data.accounts.0.bot_acceso4_url', null);
});

test('administrator can create another administrator', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/dashboard/admins', [
        'name' => 'Nuevo Admin',
        'email' => 'nuevo-admin@example.com',
        'password' => 'secure-password',
    ])->assertCreated();

    expect(User::query()->where('email', 'nuevo-admin@example.com')->first()?->role)
        ->toBe(User::ROLE_ADMIN);
});

test('secure bot links require authentication', function () {
    $this->get('/bot-links/abrir?payload=invalid')
        ->assertRedirect('/login');
});
