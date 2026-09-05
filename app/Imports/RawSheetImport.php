<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Reads a spreadsheet as raw rows — header row included, no heading mapping.
 *
 * Used by interactive importers that let the user map the file's own columns
 * onto system fields instead of forcing a fixed header layout.
 */
class RawSheetImport implements ToArray, WithLimit
{
    private array $rows = [];

    public function __construct(private int $maxRows = 500) {}

    public function limit(): int
    {
        // +1 for the header row itself
        return $this->maxRows + 1;
    }

    public function array(array $rows): void
    {
        $this->rows = $rows;
    }

    /**
     * @param  \Illuminate\Http\UploadedFile|string  $file
     * @return array<int, array<int, mixed>> every row of the first sheet, header first
     */
    public function read($file): array
    {
        Excel::import($this, $file);

        return $this->rows;
    }
}
