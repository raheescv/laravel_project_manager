<?php

namespace App\Imports;

use App\Actions\Product\Inventory\UpdateAction;
use App\Actions\Product\UpdateAction as ProductUpdateAction;
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

    private array $existingById = [];

    private array $existingByCode = [];

    private array $existingByNameCategory = [];

    private array $trashedByNameCategory = [];

    public function __construct(
        private int $userId,
        private int $totalRows,
        private int $branchId,
        private array $mappings = [],
        private string $defaultType = 'product',
        private string $moduleLabel = 'Product',
        private ?int $tenantId = null,
        private string $duplicateStrategy = 'skip'
    ) {}

    public function collection(Collection $rows)
    {
        $this->preloadExistingProducts($rows);

        $filteredRows = $rows->filter(function ($row) {
            $nameColumn = $this->mappings['name'] ?? 'name';

            return $row->filter()->isNotEmpty() && ! empty($row[$nameColumn]);
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
        $idColumn = $this->mappings['id'] ?? null;
        $codeColumn = $this->mappings['code'] ?? null;
        $nameColumn = $this->mappings['name'] ?? 'name';

        $ids = $idColumn ? $rows->pluck($idColumn)->filter()->unique()->toArray() : [];
        $codes = $codeColumn ? $rows->pluck($codeColumn)->filter()->unique()->toArray() : [];
        $names = $rows->pluck($nameColumn)->filter()->unique()->toArray();

        if (! empty($ids)) {
            $this->existingById = Product::where('type', $this->defaultType)
                ->whereIn('id', $ids)
                ->pluck('id', 'id')
                ->toArray();
        }

        if (! empty($codes)) {
            $this->existingByCode = Product::where('type', $this->defaultType)
                ->whereIn('code', $codes)
                ->pluck('id', 'code')
                ->toArray();
        }

        if (! empty($names)) {
            $nameCategoryRows = Product::where('type', $this->defaultType)
                ->whereIn('name', $names)
                ->get(['id', 'name', 'main_category_id']);

            foreach ($nameCategoryRows as $row) {
                $key = $this->nameCategoryKey($row->name, $row->main_category_id);
                $this->existingByNameCategory[$key] = $row->id;
            }

            $trashedRows = Product::onlyTrashed()
                ->where('type', $this->defaultType)
                ->whereIn('name', $names)
                ->get(['id', 'name', 'main_category_id']);

            foreach ($trashedRows as $row) {
                $key = $this->nameCategoryKey($row->name, $row->main_category_id);
                $this->trashedByNameCategory[$key] = $row->id;
            }
        }
    }

    private function nameCategoryKey(?string $name, ?int $categoryId): string
    {
        return strtolower(trim((string) $name)).'|'.($categoryId ?? '');
    }

    private function findExistingProductId(array $productRow, array $data): ?int
    {
        $idValue = $productRow['id'] ?? null;
        if ($idValue !== null && $idValue !== '') {
            $idValue = (int) $idValue;
            if (isset($this->existingById[$idValue])) {
                return $this->existingById[$idValue];
            }
        }

        $codeValue = $productRow['code'] ?? null;
        if ($codeValue !== null && $codeValue !== '' && isset($this->existingByCode[$codeValue])) {
            return $this->existingByCode[$codeValue];
        }

        $key = $this->nameCategoryKey($data['name'] ?? null, $data['main_category_id'] ?? null);
        if (isset($this->existingByNameCategory[$key])) {
            return $this->existingByNameCategory[$key];
        }

        return null;
    }

    private function processProductRow($value): void
    {
        $productRow = [];
        foreach ($this->mappings as $internalField => $excelHeader) {
            if ($excelHeader && isset($value[$excelHeader])) {
                $productRow[$internalField] = $value[$excelHeader];
            }
        }

        $productRow['name'] = trim($productRow['name']);
        if (empty($productRow['name'])) {
            return;
        }

        $data = Product::constructData($productRow, $this->userId);
        $data['type'] = $this->defaultType;

        $productName = $data['name'];
        $quantity = $productRow['stock'] ?? $value['stock'] ?? 0;

        $existingId = $this->findExistingProductId($productRow, $data);

        if ($existingId) {
            if ($this->duplicateStrategy === 'skip') {
                return;
            }

            $model = Product::find($existingId);
            if (! $model) {
                throw new Exception($this->moduleLabel.' not found for update: '.$productName);
            }
            $data['id'] = $model->id;
            validationHelper(Product::rules($data, $data['id']), $data, $this->moduleLabel);

            $productResponse = (new ProductUpdateAction())->execute($data, $data['id'], $this->userId);
            if (! $productResponse['success']) {
                throw new Exception($productResponse['message'], 1);
            }

            $inventory = Inventory::where('branch_id', $this->branchId)->where('product_id', $model->id)->first();
            if ($inventory && $this->defaultType !== 'service') {
                $inventoryData = $inventory->toArray();
                $inventoryData['quantity'] = $quantity;
                $inventoryData['remarks'] = 'Bulk Update';

                $inventoryResponse = (new UpdateAction())->execute($inventoryData, $inventory->id);
                if (! $inventoryResponse['success']) {
                    throw new Exception($inventoryResponse['message'], 1);
                }
            }

            return;
        }

        validationHelper(Product::rules($data), $data, $this->moduleLabel);
        $model = $this->createOrRestoreProduct($data);
        if ($this->defaultType !== 'service') {
            Inventory::selfCreateByProduct($model, $this->userId, $quantity, $this->branchId);
        }
    }

    private function createOrRestoreProduct(array $data): Product
    {
        $key = $this->nameCategoryKey($data['name'] ?? null, $data['main_category_id'] ?? null);

        if (isset($this->trashedByNameCategory[$key])) {
            $trashedProduct = Product::withTrashed()->find($this->trashedByNameCategory[$key]);
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

        Log::error($this->moduleLabel.' import error', $errorData);
    }

    private function updateProgress(): void
    {
        $progress = ($this->processedRows / $this->totalRows) * 100;
        event(new FileImportProgress($this->userId, $this->moduleLabel, $progress));
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
            event(new FileImportCompleted($this->userId, $this->moduleLabel, $this->errors));
        }
    }
}
