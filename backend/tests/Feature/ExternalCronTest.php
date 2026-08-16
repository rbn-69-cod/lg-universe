<?php

use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    putenv('CRON_TOKEN=cron-test-token');
    $_ENV['CRON_TOKEN'] = 'cron-test-token';
    $_SERVER['CRON_TOKEN'] = 'cron-test-token';
});

afterEach(function () {
    putenv('CRON_TOKEN');
    unset($_ENV['CRON_TOKEN'], $_SERVER['CRON_TOKEN']);
});

test('external cron rejects requests without token', function () {
    Artisan::shouldReceive('call')->never();

    $this->get('/cron/procesar-emails')
        ->assertForbidden();
});

test('external cron rejects requests with invalid token', function () {
    Artisan::shouldReceive('call')->never();

    $this->get('/cron/procesar-emails?token=wrong-token')
        ->assertForbidden();
});

test('external cron accepts correct token without authenticated user', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('emails:procesar-pedidos')
        ->andReturn(0);

    $this->assertGuest();

    $this->get('/cron/procesar-emails?token=cron-test-token')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
        ]);
});
