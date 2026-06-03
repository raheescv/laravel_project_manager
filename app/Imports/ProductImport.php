<?php

namespace App\Imports;

use App\Actions\Product\Inventory\UpdateAction as InventoryUpdateAction;
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

    /** @var array<int, Product> */
    private array $existingById = [];

    /** @var array<string, Product> */
    private array $existingByName = [];

    /** @var array<string, int> */
    private array $trashedByName = [];

    /** @var array<int, Inventory> */
    private array $inventoryByProductId = [];

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
        $nameColumn = $this->mappings['name'] ?? 'name';
        $filteredRows = $rows->filter(
            fn ($row) => $row->filter()->isNotEmpty() && ! empty($row[$nameColumn])
        );

        $this->preloadExistingProducts($filteredRows);

        foreach ($filteredRows as $value) {
            try {
                $this->processProductRow($value);
            } catch (\Throwable $th) {
                $this->handleError($value, $th);
            }
        }

        $this->processedRows += $filteredRows->count();
        $this->updateProgress();
    }

    private function preloadExistingProducts(Collection $rows): void
    {
        $idColumn = $this->mappings['id'] ?? null;
        $nameColumn = $this->mappings['name'] ?? 'name';

        $ids = $idColumn
            ? $rows->pluck($idColumn)->filter()->map(fn ($v) => (int) $v)->unique()->values()->all()
            : [];
        $names = $rows->pluck($nameColumn)->filter()->unique()->values()->all();

        if (empty($ids) && empty($names)) {
            return;
        }

        $activeRows = Product::where('type', $this->defaultType)
            ->where(function ($q) use ($ids, $names) {
                if (! empty($ids)) {
                    $q->whereIn('id', $ids);
                }
                if (! empty($names)) {
                    $q->orWhereIn('name', $names);
                }
            })
            ->get(['id', 'name', 'barcode_number']);

        foreach ($activeRows as $row) {
            $this->existingById[$row->id] = $row;
            $this->existingByName[$this->nameKey($row->name)] = $row;
        }

        if (! empty($names)) {
            $trashedRows = Product::onlyTrashed()
                ->where('type', $this->defaultType)
                ->whereIn('name', $names)
                ->get(['id', 'name']);

            foreach ($trashedRows as $row) {
                $this->trashedByName[$this->nameKey($row->name)] = $row->id;
            }
        }

        if ($this->duplicateStrategy !== 'update' || $this->defaultType === 'service') {
            return;
        }

        $matchedIds = array_keys($this->existingById);
        if (empty($matchedIds)) {
            return;
        }

        $this->inventoryByProductId = Inventory::where('branch_id', $this->branchId)
            ->whereIn('product_id', $matchedIds)
            ->get()
            ->keyBy('product_id')
            ->all();
    }

    private function nameKey(?string $name): string
    {
        return strtolower(trim((string) $name));
    }

    private function findExistingProduct(array $productRow, array $data): ?Product
    {
        $idValue = $productRow['id'] ?? null;
        if ($idValue !== null && $idValue !== '') {
            $idValue = (int) $idValue;
            if (isset($this->existingById[$idValue])) {
                return $this->existingById[$idValue];
            }
        }

        $nameKey = $this->nameKey($data['name'] ?? null);
        if ($nameKey !== '' && isset($this->existingByName[$nameKey])) {
            return $this->existingByName[$nameKey];
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

        $productRow['name'] = trim($productRow['name'] ?? '');
        if (empty($productRow['name'])) {
            return;
        }

        $data = Product::constructData($productRow, $this->userId);
        $data['type'] = $this->defaultType;

        $quantity = $productRow['stock'] ?? $value['stock'] ?? 0;

        $existing = $this->findExistingProduct($productRow, $data);

        if ($existing) {
            if ($this->duplicateStrategy === 'skip') {
                return;
            }

            $this->updateExistingProduct($existing, $data, $productRow, $quantity);

            return;
        }

        validationHelper(Product::rules($data), $data, $this->moduleLabel);
        $model = $this->createOrRestoreProduct($data);

        if ($this->defaultType !== 'service') {
            Inventory::selfCreateByProduct($model, $this->userId, $quantity, $this->branchId);
        }
    }

    private function updateExistingProduct(Product $existing, array $data, array $productRow, $quantity): void
    {
        $data['id'] = $existing->id;

        // Keep the existing barcode on update unless the import row supplies a new one.
        if (empty($productRow['barcode'] ?? null)) {
            $data['barcode_number'] = $existing->barcode_number;
        }

        $productResponse = (new ProductUpdateAction())->execute($data, $existing->id, $this->userId);
        if (! $productResponse['success']) {
            throw new Exception($productResponse['message'], 1);
        }

        if ($this->defaultType === 'service') {
            return;
        }

        $inventory = $this->inventoryByProductId[$existing->id] ?? null;
        if (! $inventory) {
            return;
        }

        // Stock unchanged: skip the no-op write that would only re-stamp 'Bulk Update' and recalc cost.
        if ($quantity == 0 || (float) $quantity === (float) $inventory->quantity) {
            return;
        }

        $inventoryData = $inventory->toArray();
        $inventoryData['quantity'] = $quantity;
        $inventoryData['remarks'] = 'Bulk Update';

        $inventoryResponse = (new InventoryUpdateAction())->execute($inventoryData, $inventory->id);
        if (! $inventoryResponse['success']) {
            throw new Exception($inventoryResponse['message'], 1);
        }
    }

    private function createOrRestoreProduct(array $data): Product
    {
        $nameKey = $this->nameKey($data['name'] ?? null);

        if ($nameKey !== '' && isset($this->trashedByName[$nameKey])) {
            $trashed = Product::withTrashed()->find($this->trashedByName[$nameKey]);
            if ($trashed) {
                $trashed->restore();
                $trashed->update($data);

                return $trashed;
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
