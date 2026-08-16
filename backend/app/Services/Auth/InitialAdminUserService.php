<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class InitialAdminUserService
{
    /**
     * @return array{user: User, created: bool}
     */
    public function ensure(?string $name = null, ?string $email = null, ?string $password = null): array
    {
        $name = trim((string) ($name ?: config('admin.initial.name')));
        $email = mb_strtolower(trim((string) ($email ?: config('admin.initial.email'))));
        $password = (string) ($password ?: config('admin.initial.password'));

        if ($name === '') {
            $name = 'Administrador';
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'ADMIN_EMAIL' => 'ADMIN_EMAIL debe contener un correo valido.',
            ]);
        }

        if ($password === '' || mb_strlen($password) < 8) {
            throw ValidationException::withMessages([
                'ADMIN_PASSWORD' => 'ADMIN_PASSWORD debe tener al menos 8 caracteres.',
            ]);
        }

        $user = User::query()->where('email', $email)->first();
        $created = $user === null;

        $attributes = [
            'name' => $name,
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ];

        if ($created || ! Hash::check($password, (string) $user->password)) {
            $attributes['password'] = Hash::make($password);
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            $attributes,
        );

        return [
            'user' => $user,
            'created' => $created,
        ];
    }
}
