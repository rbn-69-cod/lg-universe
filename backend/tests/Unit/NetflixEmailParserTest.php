<?php

use App\Services\Email\NetflixEmailParser;

it('parses a netflix login code email and preserves the 4 digit code', function () {
    $parser = new NetflixEmailParser;

    $result = $parser->parse(
        'Netflix - Ingresa este código para iniciar sesión',
        '<html><body><h1>Ingresa este código para iniciar sesión</h1><p>6108</p><p>El código vence en 15 minutos.</p></body></html>',
        "Netflix\n\nIngresa este código para iniciar sesión\n\n6108\n\nEl código vence en 15 minutos."
    );

    expect($result['type'])->toBe('login_code');
    expect($result['code'])->toBe('6108');
    expect($result['action_url'])->toBeNull();
    expect($result['duration_minutes'])->toBe(15);
});

it('parses a netflix household update email and extracts the original approval href', function () {
    $parser = new NetflixEmailParser;
    $url = 'https://www.netflix.com/account/travel/verify?token=a1b2c3&device=LG%20TV&lang=es';

    $result = $parser->parse(
        '¿Solicitaste actualizar tu Hogar con Netflix?',
        '<html><body><a href="'.$url.'">Sí, la envié yo</a><p>El enlace vence en 15 minutos.</p></body></html>',
        "Solicitaste actualizar tu Hogar con Netflix\nSi, la envie yo\nEl enlace vence en 15 minutos."
    );

    expect($result['type'])->toBe('household_update');
    expect($result['code'])->toBeNull();
    expect($result['action_url'])->toBe($url);
    expect($result['duration_minutes'])->toBe(15);
});

it('parses a netflix temporary access email and extracts the original button href with long query params', function () {
    $parser = new NetflixEmailParser;
    $url = 'https://www.netflix.com/account/travel/verify?nftoken=abc123%2F456&device=LG%20Smart%20TV&redirect=https%3A%2F%2Fwww.netflix.com%2Fbrowse%3Fjbv%3D999';

    $result = $parser->parse(
        'Tu codigo de acceso temporal',
        '<html><body><a href="'.$url.'">Obtener código</a><p>El enlace vence en 15 minutos.</p></body></html>',
        "Tu código de acceso temporal\nObtener codigo\nEl enlace vence en 15 minutos."
    );

    expect($result['type'])->toBe('temporary_access');
    expect($result['code'])->toBeNull();
    expect($result['action_url'])->toBe($url);
    expect($result['duration_minutes'])->toBe(15);
});

it('marks unrelated netflix emails as unknown instead of inventing a code or link', function () {
    $parser = new NetflixEmailParser;

    $result = $parser->parse(
        'Netflix',
        '<html><body><p>Tu membresía fue actualizada.</p><p>Número interno 6108.</p></body></html>',
        'Tu membresía fue actualizada. Número interno 6108.'
    );

    expect($result['type'])->toBe('unknown');
    expect($result['code'])->toBeNull();
    expect($result['action_url'])->toBeNull();
});
