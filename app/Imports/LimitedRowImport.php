<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Reads only the first N data rows from a spreadsheet to avoid memory exhaustion.
 */
class LimitedRowImport implements ToArray, WithLimit
{
    private int $maxRows;

    private array $rows = [];

    public function __construct(int $maxRows = 10)
    {
        $this->maxRows = $maxRows;
    }

    public function limit(): int
    {
        // +1 to account for header row
        return $this->maxRows + 1;
    }

    public function array(array $rows): void
    {
        // Skip header (row 0), take only data rows
        $this->rows = array_slice($rows, 1, $this->maxRows);
    }

    public function toArray(string $filePath): array
    {
        Excel::import($this, $filePath);

        return $this->rows;
    }
}
