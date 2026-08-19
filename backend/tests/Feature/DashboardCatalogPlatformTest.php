<?php

use App\Models\Plataforma;
use App\Models\User;

test('dashboard can create a platform with duration prices', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->postJson('/api/v1/dashboard/catalog', [
        'nombre' => 'Netflix',
        'imagen' => 'https://example.com/netflix.png',
        'precio' => 15,
        'descripcion' => 'Streaming',
        'features' => "4K\n1 perfil",
        'activacion' => 'Entrega manual',
        'terminos' => 'No cambiar datos',
        'activo' => true,
        'duraciones' => [
            ['duracion_meses' => 1, 'precio' => 15, 'activo' => true],
            ['duracion_meses' => 2, 'precio' => 28, 'activo' => true],
            ['duracion_meses' => 3, 'precio' => 40, 'activo' => false],
            ['duracion_meses' => 6, 'precio' => 75, 'activo' => false],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.catalog.0.nombre', 'Netflix')
        ->assertJsonPath('data.catalog.0.activo', true)
        ->assertJsonPath('data.catalog.0.duraciones.0.duracion_meses', 1)
        ->assertJsonPath('data.catalog.0.duraciones.1.duracion_meses', 2);

    $platform = Plataforma::query()->where('nombre', 'Netflix')->firstOrFail();
    expect($platform->duraciones()->count())->toBe(4);
    expect((float) $platform->duraciones()->where('duracion_meses', 2)->firstOrFail()->precio)->toBe(28.0);
});

test('dashboard can update active state and duration prices for a platform', function () {
    $this->actingAs(User::factory()->admin()->create());

    $platform = Plataforma::query()->create([
        'nombre' => 'Disney',
        'imagen' => null,
        'precio' => 10,
        'descripcion' => null,
        'features' => ['Premium'],
        'activacion' => null,
        'terminos' => null,
        'activo' => true,
        'orden' => 1,
    ]);

    foreach ([[1, 10, true], [2, 18, false], [3, 25, false], [6, 48, false]] as [$months, $price, $active]) {
        $platform->duraciones()->create([
            'duracion_meses' => $months,
            'precio' => $price,
            'activo' => $active,
        ]);
    }

    $this->putJson("/api/v1/dashboard/catalog/{$platform->id}", [
        'nombre' => 'Disney Plus',
        'imagen' => '',
        'precio' => 11,
        'descripcion' => 'Editada',
        'features' => "Premium\nSin anuncios",
        'activacion' => 'Auto',
        'terminos' => 'No compartir',
        'activo' => false,
        'duraciones' => [
            ['duracion_meses' => 1, 'precio' => 11, 'activo' => false],
            ['duracion_meses' => 2, 'precio' => 20, 'activo' => true],
            ['duracion_meses' => 3, 'precio' => 29, 'activo' => true],
            ['duracion_meses' => 6, 'precio' => 55, 'activo' => false],
        ],
    ])->assertOk()
        ->assertJsonPath('data.catalog.0.nombre', 'Disney Plus')
        ->assertJsonPath('data.catalog.0.activo', false);

    $platform->refresh();

    expect($platform->nombre)->toBe('Disney Plus');
    expect($platform->activo)->toBeFalse();
    expect((float) $platform->duraciones()->where('duracion_meses', 2)->firstOrFail()->precio)->toBe(20.0);
    expect($platform->duraciones()->where('duracion_meses', 2)->firstOrFail()->activo)->toBeTrue();
});
