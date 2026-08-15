<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureAdminUser extends Command
{
    protected $signature = 'admin:ensure-user
        {--email=igr.ruben@gmail.com : Admin email}
        {--password=123456789 : Admin password}
        {--name=Igr Ruben : Admin name}';

    protected $description = 'Crea o actualiza el usuario administrador principal';

    public function handle(): int
    {
        $email = mb_strtolower((string) $this->option('email'));
        $password = (string) $this->option('password');
        $name = (string) $this->option('name');

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $this->info(($user->wasRecentlyCreated ? 'Usuario creado: ' : 'Usuario actualizado: ').$email);

        return self::SUCCESS;
    }
}
