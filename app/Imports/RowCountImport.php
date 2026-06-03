<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Counts non-empty data rows without materializing the whole sheet in memory.
 * WithHeadingRow excludes the header row from the count.
 */
class RowCountImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    private int $count = 0;

    public function collection(Collection $rows): void
    {
        $this->count += $rows->filter(fn ($row) => $row->filter()->isNotEmpty())->count();
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
