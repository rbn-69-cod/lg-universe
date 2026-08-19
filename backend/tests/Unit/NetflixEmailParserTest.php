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

it('parses the real login email pattern and extracts code 7377 exactly', function () {
    $parser = new NetflixEmailParser;

    $result = $parser->parse(
        'Netflix',
        '<html><body><p>Ingresa este código para iniciar sesión</p><p>7377</p><p>Ingresa este código en tu dispositivo para iniciar sesión en Netflix.</p></body></html>',
        "Netflix\n\nIngresa este código para iniciar sesión\n\n7377\n\nIngresa este código en tu dispositivo para iniciar sesión en Netflix."
    );

    expect($result['type'])->toBe('login_code');
    expect($result['code'])->toBe('7377');
    expect($result['action_url'])->toBeNull();
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

it('prefers the exact netflix button href when the email contains multiple links', function () {
    $parser = new NetflixEmailParser;
    $expectedUrl = 'https://www.netflix.com/account/travel/verify?token=real-button';
    $otherUrl = 'https://www.netflix.com/help?track=footer';

    $result = $parser->parse(
        '¿Solicitaste actualizar tu Hogar con Netflix?',
        '<html><body>'
        .'<a href="'.$otherUrl.'">Centro de ayuda</a>'
        .'<a href="'.$expectedUrl.'"><span>Sí, la</span> <strong>envié yo</strong></a>'
        .'</body></html>',
        "Centro de ayuda\nSi, la envie yo"
    );

    expect($result['type'])->toBe('household_update');
    expect($result['action_url'])->toBe($expectedUrl);
});

it('does not invent a fallback link when multiple urls exist but no netflix button match was found', function () {
    $parser = new NetflixEmailParser;

    $result = $parser->parse(
        'Tu código de acceso temporal',
        '<html><body>'
        .'<a href="https://www.netflix.com/help?one">Ayuda</a>'
        .'<a href="https://www.netflix.com/help?two">Soporte</a>'
        .'</body></html>',
        "Tu código de acceso temporal\nAyuda\nSoporte"
    );

    expect($result['type'])->toBe('temporary_access');
    expect($result['action_url'])->toBeNull();
});

it('rejects download or asset links even if they match the button text and keeps the real netflix action url', function () {
    $parser = new NetflixEmailParser;
    $downloadUrl = 'https://www.netflix.com/assets/button/download.pdf';
    $realUrl = 'https://www.netflix.com/account/travel/verify?token=real-household';

    $result = $parser->parse(
        '¿Solicitaste actualizar tu Hogar con Netflix?',
        '<html><body>'
        .'<a href="'.$downloadUrl.'">Sí, la envié yo</a>'
        .'<a href="'.$realUrl.'">Sí, la envié yo</a>'
        .'</body></html>',
        "Sí, la envié yo"
    );

    expect($result['type'])->toBe('household_update');
    expect($result['action_url'])->toBe($realUrl);
});

it('parses the real household update pattern and returns only the full href of "Si, la envie yo"', function () {
    $parser = new NetflixEmailParser;
    $actionUrl = 'https://www.netflix.com/account/travel/verify?g=fa751d93-0da3-4d42-9475-7d6d848de4de&email=adamsultes102%40gmail.com&lang=es-CO';
    $passwordUrl = 'https://www.netflix.com/password?track=security-warning';
    $helpUrl = 'https://help.netflix.com/es/node/12345';

    $result = $parser->parse(
        'Netflix',
        '<html><body>'
        .'<h1>¿Solicitaste actualizar tu Hogar con Netflix?</h1>'
        .'<p>Hola, Lenin:</p>'
        .'<p>Recibimos una solicitud para actualizar el Hogar con Netflix de tu cuenta el 18 de agosto, 7:56 p. m. COT.</p>'
        .'<a href="'.$passwordUrl.'">cambiar la contraseña</a>'
        .'<div><a href="'.$actionUrl.'">Sí, la envié yo</a></div>'
        .'<a href="'.$helpUrl.'">Centro de ayuda</a>'
        .'</body></html>',
        "Netflix\n¿Solicitaste actualizar tu Hogar con Netflix?\nSí, la envié yo\nEl enlace vence en 15 minutos."
    );

    expect($result['type'])->toBe('household_update');
    expect($result['code'])->toBeNull();
    expect($result['action_url'])->toBe($actionUrl);
});

it('parses the real temporary access pattern and returns only the full href of "Obtener codigo"', function () {
    $parser = new NetflixEmailParser;
    $actionUrl = 'https://www.netflix.com/account/travel/code?source=email&token=3309385c-3ba3-4455-9672-3c8880642a9c&next=%2Fbrowse%3Fjbv%3D81234';
    $logoutUrl = 'https://www.netflix.com/YourAccount?lnktrk=EMP&g=logout-all';
    $passwordUrl = 'https://www.netflix.com/password?track=temporary-access-warning';

    $result = $parser->parse(
        'Netflix',
        '<html><body>'
        .'<h1>Tu código de acceso temporal</h1>'
        .'<p>Hola, elisa:</p>'
        .'<p>Solicitud de elisa desde: Samsung - Smart TV a las 18 de agosto, 9:50 p. m. COT</p>'
        .'<div><a href="'.$actionUrl.'">Obtener código</a></div>'
        .'<a href="'.$logoutUrl.'">cerrar sesión de inmediato en todos los dispositivos que no reconozcas</a>'
        .'<a href="'.$passwordUrl.'">cambiar tu contraseña</a>'
        .'</body></html>',
        "Netflix\nTu código de acceso temporal\nObtener código\nEl enlace vence en 15 minutos."
    );

    expect($result['type'])->toBe('temporary_access');
    expect($result['code'])->toBeNull();
    expect($result['action_url'])->toBe($actionUrl);
});

it('does not return another netflix link when the button href was not extracted', function () {
    $parser = new NetflixEmailParser;
    $logoutUrl = 'https://www.netflix.com/YourAccount?lnktrk=EMP&g=logout-all';
    $passwordUrl = 'https://www.netflix.com/password?track=temporary-access-warning';

    $result = $parser->parse(
        'Netflix',
        '<html><body>'
        .'<h1>Tu código de acceso temporal</h1>'
        .'<a href="'.$logoutUrl.'">cerrar sesión de inmediato en todos los dispositivos que no reconozcas</a>'
        .'<a href="'.$passwordUrl.'">cambiar tu contraseña</a>'
        .'</body></html>',
        "Netflix\nTu código de acceso temporal\nEl enlace vence en 15 minutos."
    );

    expect($result['type'])->toBe('temporary_access');
    expect($result['action_url'])->toBeNull();
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
