<?php

namespace App\Imports;

use App\Events\FileImportProgress;
use App\Models\ProductType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductTypeImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private $processedRows = 0;

    public function __construct(private $user_id, private $totalRows) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $value) {
            $data['name'] = $value['name'];
            $exists = ProductType::where('name', $data['name'])->exists();
            if (! $exists) {
                ProductType::create($data);
            }
        }
        $this->processedRows += count($rows);
        $progress = round(($this->processedRows / $this->totalRows) * 100);
        event(new FileImportProgress($this->user_id, 'ProductType', $progress));
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
