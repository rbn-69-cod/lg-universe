<?php

namespace App\Console\Commands;

use App\Services\Excel\NetflixPremiumExcelImporter;
use Illuminate\Console\Command;

class SyncNetflixPremiumExcel extends Command
{
    protected $signature = 'excel:sync-netflix-premium';

    protected $description = 'Sincroniza Netflix Premium desde los rangos Excel activos configurados en MySQL';

    public function handle(NetflixPremiumExcelImporter $importer): int
    {
        try {
            $stats = $importer->sync();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($stats as $key => $value) {
            $this->line($key.': '.$value);
        }

        return self::SUCCESS;
    }
}
