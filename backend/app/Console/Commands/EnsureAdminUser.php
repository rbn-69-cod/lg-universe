<?php

namespace App\Console\Commands;

use App\Services\Auth\InitialAdminUserService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class EnsureAdminUser extends Command
{
    protected $signature = 'admin:ensure-user
        {--email= : Admin email. Defaults to ADMIN_EMAIL}
        {--password= : Admin password. Defaults to ADMIN_PASSWORD}
        {--name= : Admin name. Defaults to ADMIN_NAME}';

    protected $description = 'Crea o actualiza el usuario administrador principal';

    public function handle(InitialAdminUserService $service): int
    {
        try {
            $result = $service->ensure(
                $this->option('name'),
                $this->option('email'),
                $this->option('password'),
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $this->info(($result['created'] ? 'Administrador creado: ' : 'Administrador actualizado: ').$result['user']->email);

        return self::SUCCESS;
    }
}
