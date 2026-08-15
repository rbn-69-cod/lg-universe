<?php

use App\Models\Plataforma;

it('returns platform catalog data ordered by current order', function () {
    Plataforma::query()->create([
        'nombre' => 'Spotify',
        'imagen' => 'https://example.com/spotify.png',
        'precio' => 12.50,
        'features' => ['Musica premium'],
        'activo' => true,
        'orden' => 2,
    ]);

    Plataforma::query()->create([
        'nombre' => 'Netflix',
        'imagen' => 'https://example.com/netflix.png',
        'precio' => 15,
        'features' => ['Streaming 4K'],
        'activo' => true,
        'orden' => 1,
    ]);

    $this->getJson('/api/v1/plataformas')
        ->assertOk()
        ->assertJsonPath('data.0.nombre', 'Netflix')
        ->assertJsonPath('data.0.precio', 15)
        ->assertJsonPath('data.0.features.0', 'Streaming 4K')
        ->assertJsonPath('data.1.nombre', 'Spotify');
});
