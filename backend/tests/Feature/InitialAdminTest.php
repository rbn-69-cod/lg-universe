<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('initial admin command creates an administrator from options', function () {
    $this->artisan('admin:ensure-user', [
        '--name' => 'Admin LG',
        '--email' => 'admin@example.com',
        '--password' => 'secure-password',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($user->name)->toBe('Admin LG')
        ->and($user->role)->toBe(User::ROLE_ADMIN)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('secure-password', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('secure-password');
});

test('initial admin seeder uses environment values and is idempotent', function () {
    config()->set('admin.initial.name', 'Admin Produccion');
    config()->set('admin.initial.email', 'owner@example.com');
    config()->set('admin.initial.password', 'first-secret');

    $this->seed(\Database\Seeders\InitialAdminSeeder::class);
    $this->seed(\Database\Seeders\InitialAdminSeeder::class);

    expect(User::query()->where('email', 'owner@example.com')->count())->toBe(1);

    $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

    expect($user->role)->toBe(User::ROLE_ADMIN)
        ->and(Hash::check('first-secret', $user->password))->toBeTrue();
});

test('initial admin seeder updates an existing user without duplicating it', function () {
    User::factory()->create([
        'name' => 'Usuario previo',
        'email' => 'owner@example.com',
        'password' => 'old-password',
        'role' => User::ROLE_USER,
    ]);

    config()->set('admin.initial.name', 'Owner LG');
    config()->set('admin.initial.email', 'owner@example.com');
    config()->set('admin.initial.password', 'new-password');

    $this->seed(\Database\Seeders\InitialAdminSeeder::class);

    expect(User::query()->where('email', 'owner@example.com')->count())->toBe(1);

    $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

    expect($user->name)->toBe('Owner LG')
        ->and($user->role)->toBe(User::ROLE_ADMIN)
        ->and(Hash::check('new-password', $user->password))->toBeTrue();
});

test('normal users cannot access dashboard administration', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard')->assertForbidden();
    $this->getJson('/api/v1/dashboard')->assertForbidden();
    $this->get('/admin/plataformas')->assertForbidden();
});

test('administrator can access dashboard administration', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/dashboard')->assertRedirect('http://localhost:4200/dashboard');
    $this->getJson('/api/v1/dashboard')->assertOk();
});
