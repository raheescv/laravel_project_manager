<?php

namespace App\Imports;

use App\Actions\Product\ProductPrice\CreateAction;
use App\Actions\Product\ProductPrice\UpdateAction;
use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ServiceImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private $processedRows = 0;

    private $errors = [];

    public function __construct(private $userId, private $totalRows, private $branchId = null, private array $mappings = []) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $value) {
            try {
                // Use mappings if provided, otherwise use direct column access
                $productRow = [];
                if (! empty($this->mappings)) {
                    foreach ($this->mappings as $internalField => $excelHeader) {
                        if ($excelHeader && isset($value[$excelHeader])) {
                            $productRow[$internalField] = $value[$excelHeader];
                        }
                    }
                } else {
                    $productRow = $value->toArray();
                }

                $name = $productRow['name'] ?? $value['name'] ?? null;
                if (! $name) {
                    continue;
                }

                $data = Product::constructData($productRow, $this->userId);
                $data['name'] = trim($data['name']);
                $data['mrp'] = $productRow['price'] ?? $value['price'] ?? $productRow['mrp'] ?? 0;
                $data['code'] = $data['code'] ?? rand(999, 9999);
                $data['status'] = $data['status'] ?? 'active';
                $data['type'] = 'service';
                $data['cost'] = $productRow['price'] ?? $value['price'] ?? $productRow['cost'] ?? 0;
                $home_service = $productRow['home_service'] ?? $value['home_service'] ?? 0;

                $productName = $data['name'];

                // Filter by tenant when checking for existing products
                $exists = Product::where('name', $productName)->first();

                $model = $exists;
                if (! $exists) {
                    $trashedExists = Product::withTrashed()->firstWhere('name', $data['name']);
                    if ($trashedExists) {
                        $trashedExists->restore();
                        $trashedExists->update($data);
                        $model = $trashedExists;
                    } else {
                        $model = Product::create($data);
                    }
                    Inventory::selfCreateByProduct($model, $this->userId, $quantity = 0, $this->branchId);
                }
                if ($home_service) {
                    $priceCheck = ProductPrice::where('product_id', $model->id)->where('price_type', 'home_service')->first();
                    $priceData = [
                        'product_id' => $model->id,
                        'price_type' => 'home_service',
                        'amount' => $home_service,
                    ];
                    if ($priceCheck) {
                        $response = (new UpdateAction())->execute($priceData, $priceCheck->id);
                    } else {
                        $response = (new CreateAction())->execute($priceData);
                    }
                    if (! $response['success']) {
                        throw new \Exception($response['message'], 1);
                    }
                }
            } catch (\Throwable $th) {
                $data['message'] = $th->getMessage();
                $this->errors[] = $data;
            }
        }
        $this->processedRows += count($rows);
        $progress = ($this->processedRows / $this->totalRows) * 100;
        event(new FileImportProgress($this->userId, 'Product', $progress));
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function __destruct()
    {
        if ($this->errors) {
            event(new FileImportCompleted($this->userId, 'Product', $this->errors));
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
