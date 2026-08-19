<?php

use App\Models\Plataforma;

it('returns platform catalog data ordered by current order', function () {
    Plataforma::query()->delete();

    $spotify = Plataforma::query()->create([
        'nombre' => 'Spotify',
        'imagen' => 'https://example.com/spotify.png',
        'precio' => 12.50,
        'features' => ['Musica premium'],
        'activo' => true,
        'orden' => 2,
    ]);

    $spotify->duraciones()->create([
        'duracion_meses' => 1,
        'precio' => 12.50,
        'activo' => true,
    ]);

    $netflix = Plataforma::query()->create([
        'nombre' => 'Netflix',
        'imagen' => 'https://example.com/netflix.png',
        'precio' => 15,
        'features' => ['Streaming 4K'],
        'activo' => true,
        'orden' => 1,
    ]);

    $netflix->duraciones()->create([
        'duracion_meses' => 1,
        'precio' => 15,
        'activo' => true,
    ]);

    $netflix->duraciones()->create([
        'duracion_meses' => 3,
        'precio' => 40,
        'activo' => true,
    ]);

    $hidden = Plataforma::query()->create([
        'nombre' => 'Oculta',
        'imagen' => null,
        'precio' => 99,
        'features' => [],
        'activo' => false,
        'orden' => 3,
    ]);

    $hidden->duraciones()->create([
        'duracion_meses' => 1,
        'precio' => 99,
        'activo' => true,
    ]);

    $this->getJson('/api/v1/plataformas')
        ->assertOk()
        ->assertJsonPath('data.0.nombre', 'Netflix')
        ->assertJsonPath('data.0.precio', 15)
        ->assertJsonPath('data.0.features.0', 'Streaming 4K')
        ->assertJsonPath('data.0.duraciones.0.duracion_meses', 1)
        ->assertJsonPath('data.0.duraciones.1.duracion_meses', 3)
        ->assertJsonPath('data.1.nombre', 'Spotify')
        ->assertJsonMissing(['nombre' => 'Oculta']);
});

it('does not break catalog when an active platform has no durations configured', function () {
    Plataforma::query()->delete();

    Plataforma::query()->create([
        'nombre' => 'Basica',
        'imagen' => null,
        'precio' => 8.50,
        'features' => ['Plan base'],
        'activo' => true,
        'orden' => 1,
    ]);

    $this->getJson('/api/v1/plataformas')
        ->assertOk()
        ->assertJsonPath('data.0.nombre', 'Basica')
        ->assertJsonPath('data.0.precio', 8.5)
        ->assertJsonPath('data.0.duraciones', []);
});
