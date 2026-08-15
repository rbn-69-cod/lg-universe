<?php

namespace App\Services\Excel;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NetflixPremiumExcelMapper
{
    public function accountCredentials(Worksheet $sheet, int $row, array $columns): array
    {
        return [
            'email' => $this->text($sheet->getCell($this->cell($columns['correo'] ?? 'U', $row))->getCalculatedValue()),
            'password' => $this->text($sheet->getCell($this->cell($columns['password'] ?? 'V', $row))->getCalculatedValue()),
        ];
    }

    public function mapRow(Worksheet $sheet, int $row, string $email, ?string $password, array $columns): ?array
    {
        $perfil = $this->text($sheet->getCell($this->cell($columns['perfil'] ?? 'F', $row))->getCalculatedValue());

        if ($perfil === '' || $email === '') {
            return null;
        }

        $estado = $this->text($sheet->getCell($this->cell($columns['estado'] ?? 'N', $row))->getCalculatedValue());

        return [
            'cuenta' => [
                'email' => mb_strtolower($email),
                'password' => $password,
                'activo' => ! $this->isExpired($estado),
            ],
            'perfil' => [
                'nombre_perfil' => $perfil,
                'pin' => $this->pin($sheet->getCell($this->cell($columns['pin'] ?? 'G', $row))),
                'numero' => $this->text($sheet->getCell($this->cell($columns['numero'] ?? 'H', $row))->getCalculatedValue()),
                'vendedor' => $this->seller(
                    $sheet->getCell($this->cell($columns['vendedor_igarlos'] ?? 'I', $row))->getCalculatedValue(),
                    $sheet->getCell($this->cell($columns['vendedor_nikol'] ?? 'J', $row))->getCalculatedValue()
                ),
                'costo' => $this->decimal($sheet->getCell($this->cell($columns['costo'] ?? 'K', $row))->getCalculatedValue()),
                'fecha_inicio' => $this->dateCell($sheet->getCell($this->cell($columns['fecha_inicio'] ?? 'L', $row))),
                'fecha_fin' => $this->dateCell($sheet->getCell($this->cell($columns['fecha_fin'] ?? 'M', $row))),
                'estado_excel' => $estado,
                'ocupado' => $this->isOccupied($estado),
                'source_row' => $row,
                'cliente_acceso_usuario' => $this->text($sheet->getCell($this->cell($columns['cliente_acceso_usuario'] ?? 'X', $row))->getCalculatedValue()),
            ],
        ];
    }

    private function seller(mixed $igarlos, mixed $nikol): ?string
    {
        $sellers = [];

        if ($this->text($igarlos) !== '') {
            $sellers[] = 'IGARLOS';
        }

        if ($this->text($nikol) !== '') {
            $sellers[] = 'NIKOL';
        }

        return $sellers === [] ? null : implode('/', $sellers);
    }

    private function isOccupied(string $estado): bool
    {
        $estado = $this->normalize($estado);

        return $estado !== '' && ! str_contains($estado, 'vencido');
    }

    private function isExpired(string $estado): bool
    {
        return str_contains($this->normalize($estado), 'vencido');
    }

    private function text(mixed $value): string
    {
        return trim((string) $value);
    }

    private function pin(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): string
    {
        $value = $this->text($cell->getFormattedValue());

        if ($value === '') {
            $value = $this->text($cell->getCalculatedValue());
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        return str_pad(substr($digits, -4), 4, '0', STR_PAD_LEFT);
    }

    private function decimal(mixed $value): ?float
    {
        $value = str_replace(',', '.', $this->text($value));

        return is_numeric($value) ? (float) $value : null;
    }

    private function dateCell(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell): ?string
    {
        foreach ([$cell->getCalculatedValue(), $cell->getFormattedValue(), $cell->getValue()] as $value) {
            $date = $this->date($value);

            if ($date !== null) {
                return $date;
            }
        }

        return null;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        $raw = $this->normalize($this->text($value));
        $year = now()->year;
        $months = [
            'ene' => 1,
            'enero' => 1,
            'feb' => 2,
            'febrero' => 2,
            'mar' => 3,
            'marzo' => 3,
            'abr' => 4,
            'abril' => 4,
            'may' => 5,
            'mayo' => 5,
            'jun' => 6,
            'junio' => 6,
            'jul' => 7,
            'julio' => 7,
            'ago' => 8,
            'agosto' => 8,
            'sep' => 9,
            'set' => 9,
            'sept' => 9,
            'septiembre' => 9,
            'setiembre' => 9,
            'oct' => 10,
            'octubre' => 10,
            'nov' => 11,
            'noviembre' => 11,
            'dic' => 12,
            'diciembre' => 12,
        ];

        if (preg_match('/\b(\d{1,2})\s*(?:de\s*)?([a-z]+)(?:\s*(?:de\s*)?(\d{2,4}))?\b/', $raw, $matches)) {
            $month = $months[$matches[2]] ?? null;
            $dateYear = isset($matches[3]) && $matches[3] !== ''
                ? $this->fourDigitYear((int) $matches[3])
                : $year;

            return $month ? $this->safeDate($dateYear, $month, (int) $matches[1]) : null;
        }

        if (preg_match('/\b(\d{1,2})[-\/\.](\d{1,2})(?:[-\/\.](\d{2,4}))?\b/', $raw, $matches)) {
            $dateYear = isset($matches[3]) && $matches[3] !== ''
                ? $this->fourDigitYear((int) $matches[3])
                : $year;

            return $this->safeDate($dateYear, (int) $matches[2], (int) $matches[1]);
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function safeDate(int $year, int $month, int $day): ?string
    {
        try {
            return Carbon::create($year, $month, $day)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function fourDigitYear(int $year): int
    {
        if ($year < 100) {
            return $year >= 70 ? 1900 + $year : 2000 + $year;
        }

        return $year;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;

        return trim($value);
    }

    private function cell(string $column, int $row): string
    {
        $column = strtoupper(preg_replace('/[^A-Z]/i', '', $column) ?: 'A');

        return "{$column}{$row}";
    }
}
