<?php

namespace App\Services\Excel;

use App\Models\Cuenta;
use App\Models\ExcelImportRange;
use App\Models\Perfil;
use App\Models\Producto;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class NetflixPremiumExcelImporter
{
    public function __construct(
        private readonly ExcelWorkbookLoader $loader,
        private readonly NetflixPremiumExcelMapper $mapper,
    ) {}

    public function sync(?int $rangeId = null, ?string $platform = null): array
    {
        $ranges = ExcelImportRange::query()
            ->where('activo', true)
            ->when($rangeId, fn ($query) => $query->whereKey($rangeId))
            ->when($platform, fn ($query) => $query->where('plataforma', $platform))
            ->orderBy('plataforma')
            ->orderBy('hoja_excel')
            ->orderBy('fila_inicio')
            ->get();

        if ($ranges->isEmpty()) {
            throw new RuntimeException('No hay rangos activos para sincronizar.');
        }

        $this->assertNoOverlaps($ranges);

        $stats = [
            'rangos' => $ranges->count(),
            'cuentas_creadas' => 0,
            'cuentas_actualizadas' => 0,
            'perfiles_creados' => 0,
            'perfiles_actualizados' => 0,
            'filas_omitidas' => 0,
        ];

        foreach ($ranges->groupBy(fn (ExcelImportRange $range) => $range->archivo_url ?: 'default') as $sourceRanges) {
            $sourceUrl = $sourceRanges->first()->archivo_url;

            if (! $sourceUrl) {
                throw new RuntimeException('Falta URL del archivo Excel en la configuracion.');
            }

            $spreadsheet = $this->loader->load(
                $sourceUrl,
                $sourceRanges->pluck('hoja_excel')->unique()->values()->all(),
                (int) $sourceRanges->max('fila_fin'),
                $this->columnsForRanges($sourceRanges),
                (int) $sourceRanges->min('fila_inicio')
            );

            foreach ($sourceRanges->groupBy(fn (ExcelImportRange $range) => implode('|', [
                $range->plataforma,
                $range->producto_slug,
                $range->hoja_excel,
            ])) as $sheetRanges) {
                $sheetName = $sheetRanges->first()->hoja_excel;
                $sheet = $spreadsheet->getSheetByName($sheetName);

                if (! $sheet) {
                    throw new RuntimeException("La hoja '{$sheetName}' no existe en el Excel.");
                }

                $this->syncSheet($sheet, $sheetRanges->values(), $stats);
            }
        }

        ExcelImportRange::query()
            ->whereKey($ranges->pluck('id'))
            ->update([
                'ultimo_sync_at' => now(),
                'ultimo_error' => null,
            ]);

        return $stats;
    }

    private function syncSheet($sheet, Collection $ranges, array &$stats): void
    {
        $firstRange = $ranges->first();
        $platform = $firstRange->plataforma ?: 'Netflix Premium';
        $slug = Str::slug($firstRange->producto_slug ?: $platform);

        $product = Producto::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'nombre' => $platform,
                'precio' => 15,
                'tipo' => 'perfil',
                'perfiles_por_cuenta' => 5,
                'duracion_dias' => 30,
                'activo' => true,
            ]
        );

        $maxEnd = (int) $ranges->max('fila_fin');
        $currentByRange = [];

        DB::transaction(function () use ($sheet, $ranges, $maxEnd, $product, $platform, &$currentByRange, &$stats) {
            for ($row = 1; $row <= $maxEnd; $row++) {
                $rangeForCredentials = $this->rangeForCredentials($row, $ranges);
                $credentialKey = $rangeForCredentials?->id ?? 'default';
                $credentialColumns = $rangeForCredentials?->columnMap() ?? $ranges->first()->columnMap();

                $credentials = $this->mapper->accountCredentials($sheet, $row, $credentialColumns);

                if ($credentials['email'] !== '') {
                    $currentByRange[$credentialKey] = [
                        'email' => $credentials['email'],
                        'password' => $credentials['password'],
                    ];
                }

                $activeRange = $this->activeRangeForRow($row, $ranges);

                if (! $activeRange) {
                    continue;
                }

                $rangeCredentials = $currentByRange[$activeRange->id]
                    ?? $currentByRange[$credentialKey]
                    ?? ['email' => null, 'password' => null];

                $mapped = $this->mapper->mapRow(
                    $sheet,
                    $row,
                    (string) $rangeCredentials['email'],
                    $rangeCredentials['password'],
                    $activeRange->columnMap()
                );

                if (! $mapped) {
                    $stats['filas_omitidas']++;

                    continue;
                }

                $mapped['perfil'] = $this->completeProfileDates($mapped['perfil'], (int) $product->duracion_dias);

                $account = Cuenta::query()->updateOrCreate(
                    [
                        'producto_id' => $product->id,
                        'email' => $mapped['cuenta']['email'],
                    ],
                    [
                        'password' => $mapped['cuenta']['password'],
                        'perfiles_total' => 5,
                        'activo' => $mapped['cuenta']['activo'],
                        'source_platforma' => $platform,
                        'source_hoja_excel' => $sheet->getTitle(),
                        'source_row' => $row,
                    ]
                );

                $account->wasRecentlyCreated
                    ? $stats['cuentas_creadas']++
                    : $stats['cuentas_actualizadas']++;

                $profile = Perfil::query()->updateOrCreate(
                    [
                        'cuenta_id' => $account->id,
                        'nombre_perfil' => $mapped['perfil']['nombre_perfil'],
                    ],
                    $mapped['perfil'] + [
                        'source_platforma' => $platform,
                        'source_hoja_excel' => $sheet->getTitle(),
                    ]
                );

                $profile->wasRecentlyCreated
                    ? $stats['perfiles_creados']++
                    : $stats['perfiles_actualizados']++;

                $account->update([
                    'perfiles_usados' => Perfil::query()
                        ->where('cuenta_id', $account->id)
                        ->where('ocupado', true)
                        ->count(),
                ]);
            }
        });
    }

    private function rowIsInsideRanges(int $row, Collection $ranges): bool
    {
        return $ranges->contains(fn (ExcelImportRange $range) => $row >= $range->fila_inicio && $row <= $range->fila_fin);
    }

    private function completeProfileDates(array $profile, int $durationDays): array
    {
        $durationDays = max(1, $durationDays);

        try {
            if (empty($profile['fecha_fin']) && ! empty($profile['fecha_inicio'])) {
                $profile['fecha_fin'] = Carbon::parse($profile['fecha_inicio'])
                    ->addDays($durationDays)
                    ->toDateString();
            }

            if (empty($profile['fecha_inicio']) && ! empty($profile['fecha_fin'])) {
                $profile['fecha_inicio'] = Carbon::parse($profile['fecha_fin'])
                    ->subDays($durationDays)
                    ->toDateString();
            }
        } catch (\Throwable) {
            return $profile;
        }

        return $profile;
    }

    private function activeRangeForRow(int $row, Collection $ranges): ?ExcelImportRange
    {
        return $ranges->first(fn (ExcelImportRange $range) => $row >= $range->fila_inicio && $row <= $range->fila_fin);
    }

    private function rangeForCredentials(int $row, Collection $ranges): ?ExcelImportRange
    {
        return $ranges->first(fn (ExcelImportRange $range) => $row <= $range->fila_fin)
            ?? $ranges->last();
    }

    /**
     * @return array<int, string>
     */
    private function columnsForRanges(Collection $ranges): array
    {
        return $ranges
            ->flatMap(fn (ExcelImportRange $range) => array_values($range->columnMap()))
            ->unique()
            ->values()
            ->all();
    }

    private function assertNoOverlaps(Collection $ranges): void
    {
        foreach ($ranges->groupBy(fn (ExcelImportRange $range) => implode('|', [
            $range->archivo_url,
            $range->hoja_excel,
        ])) as $sheetRanges) {
            $ordered = $sheetRanges->sortBy('fila_inicio')->values();

            for ($i = 1; $i < $ordered->count(); $i++) {
                $previous = $ordered[$i - 1];
                $current = $ordered[$i];

                if ($current->fila_inicio <= $previous->fila_fin) {
                    throw new RuntimeException(
                        "Rangos activos superpuestos en hoja {$current->hoja_excel}: {$previous->plataforma} {$previous->fila_inicio}-{$previous->fila_fin} y {$current->plataforma} {$current->fila_inicio}-{$current->fila_fin}."
                    );
                }
            }
        }
    }
}
