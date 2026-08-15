<?php

namespace App\Services\Excel;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ExcelImportReadFilter implements IReadFilter
{
    /**
     * @var array<int, string>|null
     */
    private ?array $normalizedColumns = null;

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $sheetNames
     */
    public function __construct(
        private readonly int $maxRow,
        private readonly array $columns,
        private readonly array $sheetNames = [],
    ) {}

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if ($this->sheetNames !== [] && ! in_array($worksheetName, $this->sheetNames, true)) {
            return false;
        }

        if ($row > $this->maxRow) {
            return false;
        }

        return in_array(strtoupper($columnAddress), $this->normalizedColumns(), true);
    }

    /**
     * @return array<int, string>
     */
    private function normalizedColumns(): array
    {
        if ($this->normalizedColumns !== null) {
            return $this->normalizedColumns;
        }

        $normalized = [];

        foreach ($this->columns as $column) {
            $column = strtoupper(preg_replace('/[^A-Z]/i', '', $column) ?: '');

            if ($column === '') {
                continue;
            }

            $normalized[] = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($column));
        }

        $this->normalizedColumns = array_values(array_unique($normalized));

        return $this->normalizedColumns;
    }
}
