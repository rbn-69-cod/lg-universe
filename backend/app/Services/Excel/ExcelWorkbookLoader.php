<?php

namespace App\Services\Excel;

use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class ExcelWorkbookLoader
{
    /**
     * @param  array<int, string>  $sheetNames
     * @param  array<int, string>  $columns
     */
    public function load(string $url, array $sheetNames = [], ?int $maxRow = null, array $columns = [], int $minRow = 1): Spreadsheet
    {
        if ($this->googleSheetId($url) && $sheetNames !== [] && $maxRow !== null && $columns !== []) {
            return $this->loadGoogleSheetsCsv($url, $sheetNames, $minRow, $maxRow, $columns);
        }

        $downloadUrl = $this->normalizeUrl($url);
        $tmpPath = tempnam(sys_get_temp_dir(), 'excel_import_');

        if ($tmpPath === false) {
            throw new RuntimeException('No se pudo crear archivo temporal para descargar el Excel.');
        }

        try {
            $response = Http::timeout(45)->sink($tmpPath)->get($downloadUrl);

            if (! $response->successful()) {
                throw new RuntimeException('No se pudo descargar el Excel. HTTP '.$response->status());
            }

            $reader = IOFactory::createReaderForFile($tmpPath);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $reader->setIgnoreRowsWithNoCells(true);

            if ($sheetNames !== []) {
                $reader->setLoadSheetsOnly($sheetNames);
            }

            if ($maxRow !== null && $columns !== []) {
                $reader->setReadFilter(new ExcelImportReadFilter($maxRow, $columns, $sheetNames));
            }

            return $reader->load($tmpPath);
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    private function normalizeUrl(string $url): string
    {
        if ($sheetId = $this->googleSheetId($url)) {
            return 'https://docs.google.com/spreadsheets/d/'.$sheetId.'/export?format=xlsx';
        }

        return $url;
    }

    /**
     * @param  array<int, string>  $sheetNames
     * @param  array<int, string>  $columns
     */
    private function loadGoogleSheetsCsv(string $url, array $sheetNames, int $minRow, int $maxRow, array $columns): Spreadsheet
    {
        $sheetId = $this->googleSheetId($url);

        if (! $sheetId) {
            throw new RuntimeException('URL de Google Sheets invalida.');
        }

        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sheetNames as $sheetName) {
            $worksheet = new Worksheet($spreadsheet, $sheetName);
            $spreadsheet->addSheet($worksheet);

            $csvUrl = sprintf(
                'https://docs.google.com/spreadsheets/d/%s/gviz/tq?tqx=out:csv&sheet=%s&range=%s',
                $sheetId,
                rawurlencode($sheetName),
                rawurlencode('A'.$minRow.':'.$this->maxColumn($columns).$maxRow)
            );

            $tmpPath = tempnam(sys_get_temp_dir(), 'excel_import_csv_');

            if ($tmpPath === false) {
                throw new RuntimeException('No se pudo crear archivo temporal para descargar la hoja CSV.');
            }

            try {
                $response = Http::timeout(45)->sink($tmpPath)->get($csvUrl);

                if (! $response->successful()) {
                    throw new RuntimeException("No se pudo descargar la hoja '{$sheetName}'. HTTP ".$response->status());
                }

                $this->fillWorksheetFromCsv($worksheet, $tmpPath, $minRow);
            } finally {
                if (is_file($tmpPath)) {
                    @unlink($tmpPath);
                }
            }
        }

        return $spreadsheet;
    }

    private function fillWorksheetFromCsv(Worksheet $worksheet, string $path, int $startRow): void
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir la hoja CSV descargada.');
        }

        try {
            $rowNumber = $startRow;

            while (($row = fgetcsv($handle)) !== false) {
                foreach ($row as $index => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }

                    $worksheet->setCellValue(
                        Coordinate::stringFromColumnIndex($index + 1).$rowNumber,
                        $value
                    );
                }

                $rowNumber++;
            }
        } finally {
            fclose($handle);
        }
    }

    private function googleSheetId(string $url): ?string
    {
        if (preg_match('#docs\.google\.com/spreadsheets/d/([^/]+)#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function maxColumn(array $columns): string
    {
        $max = 1;

        foreach ($columns as $column) {
            $column = strtoupper(preg_replace('/[^A-Z]/i', '', $column) ?: 'A');
            $max = max($max, Coordinate::columnIndexFromString($column));
        }

        return Coordinate::stringFromColumnIndex($max);
    }
}
