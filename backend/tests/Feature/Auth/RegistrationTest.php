<?php

use Illuminate\Support\Facades\Route;

test('registration screen can be rendered', function () {
    if (! Route::has('register')) {
        $this->markTestSkipped('Registration is disabled.');
    }

    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    if (! Route::has('register.store')) {
        $this->markTestSkipped('Registration is disabled.');
    }

    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
