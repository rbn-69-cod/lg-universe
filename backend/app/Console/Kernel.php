<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Registrar comandos personalizados
     */
    protected $commands = [
        // Laravel detecta automáticamente los comandos en app/Console/Commands
    ];

    /**
     * Registrar tareas automáticas (CRON de Laravel)
     */
    protected function schedule(Schedule $schedule): void
    {
        /*
        |--------------------------------------------------------------------------
        | CRON JOB: Procesar Correos IMAP
        |--------------------------------------------------------------------------
        | Este comando se ejecutará cada 1 minuto.
        | Es el reemplazo de tu cron antiguo:
        | https://igruben.lat/procesar_emails?token=mtm256
        |
        | Ahora Laravel ejecuta el comando:
        |     php artisan emails:procesar-pedidos
        |
        */

        $schedule->command('emails:procesar-pedidos')
            ->everyMinute()
            ->withoutOverlapping(); // evita que se duplique si demora
    }

    /**
     * Registrar comandos y archivos console
     */
    protected function commands(): void
    {
        // Carga automáticamente todos los comandos de app/Console/Commands
        $this->load(__DIR__.'/Commands');

        // Carga las rutas de consola (opcionales)
        require base_path('routes/console.php');
    }
}
