<?php

namespace Database\Seeders;

use App\Services\Auth\InitialAdminUserService;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;

class InitialAdminSeeder extends Seeder
{
    public function run(InitialAdminUserService $service): void
    {
        try {
            $service->ensure();
        } catch (ValidationException $exception) {
            $this->command?->warn('Administrador inicial omitido: configura ADMIN_EMAIL y ADMIN_PASSWORD en el entorno.');
        }
    }
}
