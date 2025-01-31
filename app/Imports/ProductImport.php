<?php

namespace App\Imports;

use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private $processedRows = 0;

    private $errors = [];

    public function __construct(private $user_id, private $totalRows) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $value) {
            try {
                $data = Product::constructData($value->toArray(), $this->user_id);
                $exists = Product::firstWhere('name', $data['name']);
                if (! $exists) {
                    $trashedExists = Product::withTrashed()->firstWhere('name', $data['name']);
                    if ($trashedExists) {
                        $trashedExists->restore();
                        $trashedExists->update($data);
                        $model = $trashedExists;
                    } else {
                        $model = Product::create($data);
                    }

                    $model['quantity'] = $value['stock'] ?? 0;
                    Inventory::selfCreateByProduct($model, $this->user_id);
                }
            } catch (\Throwable $th) {
                $data['message'] = $th->getMessage();
                $this->errors[] = $data;
            }
        }
        $this->processedRows += count($rows);
        $progress = ($this->processedRows / $this->totalRows) * 100;
        event(new FileImportProgress($this->user_id, 'Product', $progress));
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function __destruct()
    {
        if ($this->errors) {
            event(new FileImportCompleted($this->user_id, 'Product', $this->errors));
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
