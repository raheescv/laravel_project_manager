<?php

namespace App\Imports;

use App\Actions\Product\UpdateAction as ProductUpdateAction;
use App\Actions\Purchase\CreateOpeningStockDraftAction;
use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Jobs\BranchInventoryCreationJob;
use App\Jobs\Product\ImportProductImagesFromUrlsJob;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Purchase;
use App\Services\ProductImageUrlImporter;
use Exception;
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

    /** @var array<int, Product> */
    private array $existingById = [];

    /** @var array<string, Product> */
    private array $existingByName = [];

    /** @var array<string, int> */
    private array $trashedByName = [];

    /** @var array<int, array<int, true>> product id => branch id, for inventory rows that exist */
    private array $inventoryExists = [];

    /** @var array<int, array<int, float>> branch id => product id => stock to receive through the draft purchases */
    private array $openingStock = [];

    /** @var array<int, string>|null branch id => mapped Excel header */
    private ?array $branchStockColumns = null;

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

        if ($this->duplicateStrategy !== 'update' || ! $this->importsStock()) {
            return;
        }

        $matchedIds = array_keys($this->existingById);
        if (empty($matchedIds)) {
            return;
        }

        $branchIds = array_keys($this->branchStockColumns());
        if (empty($branchIds)) {
            return;
        }

        $inventories = Inventory::whereIn('branch_id', $branchIds)
            ->whereIn('product_id', $matchedIds)
            ->whereNull('employee_id')
            ->get(['product_id', 'branch_id']);

        foreach ($inventories as $inventory) {
            $this->inventoryExists[$inventory->product_id][$inventory->branch_id] = true;
        }
    }

    /**
     * The stock column for each branch: `stock_branch_{id}` mappings. Without one for the
     * importing branch, the legacy `stock` mapping (or a bare `stock` header) lands there.
     * Branches outside the current tenant are dropped.
     *
     * @return array<int, string>
     */
    private function branchStockColumns(): array
    {
        if ($this->branchStockColumns !== null) {
            return $this->branchStockColumns;
        }

        $columns = [];
        foreach ($this->mappings as $field => $header) {
            if ($header && preg_match('/^stock_branch_(\d+)$/', (string) $field, $match)) {
                $columns[(int) $match[1]] = $header;
            }
        }

        if (! array_key_exists('stock_branch_'.$this->branchId, $this->mappings)) {
            $legacyHeader = array_key_exists('stock', $this->mappings) ? $this->mappings['stock'] : 'stock';
            if ($legacyHeader) {
                $columns[$this->branchId] = $legacyHeader;
            }
        }

        $tenantBranchIds = Branch::whereIn('id', array_keys($columns))->pluck('id')->all();

        return $this->branchStockColumns = array_intersect_key($columns, array_flip($tenantBranchIds));
    }

    /**
     * Positive stock per branch read from the row; blank and zero cells are left out.
     *
     * @return array<int, float>
     */
    private function branchQuantities($value): array
    {
        $quantities = [];
        foreach ($this->branchStockColumns() as $branchId => $header) {
            $cell = $value[$header] ?? null;
            if ($cell === null || trim((string) $cell) === '') {
                continue;
            }
            if (! is_numeric($cell)) {
                throw new Exception("Stock '{$cell}' in column '{$header}' is not a number.", 1);
            }
            if ($cell > 0) {
                $quantities[$branchId] = (float) $cell;
            }
        }

        return $quantities;
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

        $imageUrls = app(ProductImageUrlImporter::class)->parseUrls($productRow['image'] ?? null);
        unset($productRow['image']);

        $data = Product::constructData($productRow, $this->userId);
        $data['type'] = $this->defaultType;

        $quantities = $this->importsStock() ? $this->branchQuantities($value) : [];

        $existing = $this->findExistingProduct($productRow, $data);

        if ($existing) {
            if ($this->duplicateStrategy === 'skip') {
                return;
            }

            $this->updateExistingProduct($existing, $data, $productRow, $quantities);
            $this->queueImageDownloads($existing->id, $imageUrls);

            return;
        }

        validationHelper(Product::rules($data), $data, $this->moduleLabel);
        $model = $this->createOrRestoreProduct($data);
        $this->queueImageDownloads($model->id, $imageUrls);

        if ($this->defaultType !== 'service') {
            Inventory::selfCreateByProduct($model, $this->userId, 0, $this->branchId);
            $this->addToDraft($model->id, $quantities);
        }
    }

    /**
     * @param  array<int, float>  $quantities  branch id => stock
     */
    private function updateExistingProduct(Product $existing, array $data, array $productRow, array $quantities): void
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

        foreach (array_keys($quantities) as $branchId) {
            if (! isset($this->inventoryExists[$existing->id][$branchId])) {
                (new BranchInventoryCreationJob(Product::find($existing->id), $branchId, $this->userId))->handle();
            }
        }
        $this->addToDraft($existing->id, $quantities);
    }

    /**
     * Only products carry stock in, and only through the draft purchases: the import never moves stock itself.
     */
    private function importsStock(): bool
    {
        return $this->defaultType === 'product';
    }

    /**
     * @param  array<int, float>  $quantities  branch id => stock
     */
    private function addToDraft(int $productId, array $quantities): void
    {
        foreach ($quantities as $branchId => $quantity) {
            $this->openingStock[$branchId][$productId] = $quantity;
        }
    }

    /**
     * Turns the stock read from the sheet into one draft purchase per branch. Call once, after
     * every chunk has been read. Nothing reaches inventory until a draft is completed.
     *
     * @return array<int, Purchase> branch id => draft purchase
     */
    public function createOpeningStockDrafts(): array
    {
        $drafts = [];
        foreach ($this->openingStock as $branchId => $quantities) {
            try {
                $drafts[$branchId] = DB::transaction(function () use ($branchId, $quantities): Purchase {
                    $response = (new CreateOpeningStockDraftAction())->execute($branchId, $quantities, $this->userId);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }

                    return $response['data'];
                });
            } catch (\Throwable $th) {
                $this->errors[] = [
                    'branch_id' => $branchId,
                    'message' => 'Opening stock draft purchase was not created: '.$th->getMessage(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                ];
                Log::error($this->moduleLabel.' import opening stock error', end($this->errors));
            }
        }
        $this->openingStock = [];

        return $drafts;
    }

    /**
     * @param  array<int, string>  $imageUrls
     */
    private function queueImageDownloads(int $productId, array $imageUrls): void
    {
        if (empty($imageUrls)) {
            return;
        }

        ImportProductImagesFromUrlsJob::dispatch($productId, $imageUrls, $this->tenantId);
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
