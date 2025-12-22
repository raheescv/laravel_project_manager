<?php

namespace App\Imports;

use App\Actions\Product\Inventory\UpdateAction;
use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Inventory;
use App\Models\Product;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private int $processedRows = 0;

    private array $errors = [];

    private array $existingProducts = [];

    private array $trashedProducts = [];

    public function __construct(private int $userId, private int $totalRows, private int $branchId) {}

    public function collection(Collection $rows)
    {
        $this->preloadExistingProducts($rows);

        $filteredRows = $rows->filter(function ($row) {
            return $row->filter()->isNotEmpty() && ! empty($row['name']);
        });

        $processedInBatch = 0;

        foreach ($filteredRows as $value) {
            try {
                $processedInBatch++;
                $this->processProductRow($value);
            } catch (\Throwable $th) {
                $this->handleError($value, $th);
            }
        }
        $this->processedRows += $processedInBatch;
        $this->updateProgress();
    }

    private function preloadExistingProducts(Collection $rows): void
    {
        $productNames = $rows->pluck('name')->filter()->unique()->toArray();

        if (empty($productNames)) {
            return;
        }

        $this->existingProducts = Product::whereIn('name', $productNames)->pluck('id', 'name')->toArray();

        $this->trashedProducts = Product::onlyTrashed()->whereIn('name', $productNames)->pluck('id', 'name')->toArray();
    }

    private function processProductRow($value): void
    {
        $productData = $value->toArray();
        if (empty($productData['barcode'])) {
            $productData['barcode'] = generateBarcode();
        }
        $data = Product::constructData($productData, $this->userId);
        $data['name'] = trim($data['name']);
        if (empty($data['name'])) {
            return;
        }
        $productName = $data['name'];
        $quantity = $value['stock'] ?? 0;

        $uploadType = $productData['upload_type'] ?? 'new';
        $uploadType = strtolower($uploadType);

        if ($uploadType == 'new') {
            if (isset($this->existingProducts[$productName])) {
                return;
            }
            validationHelper(Product::rules($data), $data, 'Product');
            $model = $this->createOrRestoreProduct($data, $productName);
            Inventory::selfCreateByProduct($model, $this->userId, $quantity, $this->branchId);
        } else {
            $model = Product::find($data['id']);
            if (! $model) {
                throw new Exception('Product not found with the specified ID: '.$data['id']);
            }
            validationHelper(Product::rules($data, $data['id']), $data, 'Product');

            $inventory = Inventory::where('branch_id', $this->branchId)->where('product_id', $model->id)->first();
            $inventoryData = $inventory->toArray();
            $inventoryData['quantity'] = $quantity;
            $inventoryData['remarks'] = 'Bulk Update';

            $response = (new UpdateAction())->execute($inventoryData, $inventory->id);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
        }
    }

    private function createOrRestoreProduct(array $data, string $productName): Product
    {
        if (isset($this->trashedProducts[$productName])) {
            $trashedProduct = Product::withTrashed()->find($this->trashedProducts[$productName]);
            if ($trashedProduct) {
                $trashedProduct->restore();
                $trashedProduct->update($data);

                return $trashedProduct;
            }
        }

        return Product::create($data);
    }

    private function handleError($value, \Throwable $th): void
    {
        $errorData = $value->toArray();
        $errorData['message'] = $th->getMessage();
        $errorData['file'] = $th->getFile();
        $errorData['line'] = $th->getLine();

        $this->errors[] = $errorData;

        Log::error('Product import error', $errorData);
    }

    private function updateProgress(): void
    {
        $progress = ($this->processedRows / $this->totalRows) * 100;
        event(new FileImportProgress($this->userId, 'Product', $progress));
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function __destruct()
    {
        if (! empty($this->errors)) {
            event(new FileImportCompleted($this->userId, 'Product', $this->errors));
        }
    }
}
