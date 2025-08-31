<?php

namespace App\Imports;

use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public function __construct(private int $user_id, private int $totalRows) {}

    public function collection(Collection $rows)
    {
        // Pre-load existing products for this batch to reduce database queries
        $this->preloadExistingProducts($rows);

        $filteredRows = $rows->filter(function ($row) {
            return $row->filter()->isNotEmpty() && !empty($row['name']);
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

        // Load existing products
        $this->existingProducts = Product::whereIn('name', $productNames)->pluck('id', 'name')->toArray();

        // Load trashed products
        $this->trashedProducts = Product::withTrashed()->whereIn('name', $productNames)->whereNotNull('deleted_at')->pluck('id', 'name')->toArray();
    }

    private function processProductRow($value): void
    {
        $data = Product::constructData($value->toArray(), $this->user_id);
        $data['name'] = trim($data['name']);
        if (empty($data['name'])) {
            return;
        }

        $productName = $data['name'];

        // Check if product already exists (using preloaded data)
        if (isset($this->existingProducts[$productName])) {
            return;
        }

        // Validate data
        validationHelper(Product::rules($data), $data, 'Product');

        // Create or restore product
        $model = $this->createOrRestoreProduct($data, $productName);

        // Create inventory
        $quantity = $value['stock'] ?? 0;
        Inventory::selfCreateByProduct($model, $this->user_id, $quantity);
    }

    private function createOrRestoreProduct(array $data, string $productName): Product
    {
        // Check if product exists in trashed products
        if (isset($this->trashedProducts[$productName])) {
            $trashedProduct = Product::withTrashed()->find($this->trashedProducts[$productName]);
            if ($trashedProduct) {
                $trashedProduct->restore();
                $trashedProduct->update($data);
                return $trashedProduct;
            }
        }

        // Create new product
        return Product::create($data);
    }

    private function handleError($value, \Throwable $th): void
    {
        $errorData =  $value->toArray();
        $errorData['message'] = $th->getMessage();
        $errorData['file'] = $th->getFile();
        $errorData['line'] = $th->getLine();

        $this->errors[] = $errorData;

        Log::error('Product import error', $errorData);
    }

    private function updateProgress(): void
    {
        $progress = ($this->processedRows / $this->totalRows) * 100;
        event(new FileImportProgress($this->user_id, 'Product', $progress));
    }

    public function batchSize(): int
    {
        return 500; // Reduced from 1000 for better memory management
    }

    public function chunkSize(): int
    {
        return 500; // Reduced from 1000 for better memory management
    }

    public function __destruct()
    {
        if (!empty($this->errors)) {
            event(new FileImportCompleted($this->user_id, 'Product', $this->errors));
        }
    }
}
