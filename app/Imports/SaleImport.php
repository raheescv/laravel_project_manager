<?php

namespace App\Imports;

use App\Actions\Sale\CreateAction;
use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Account;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class SaleImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private int $processedRows = 0;

    private array $errors = [];

    private array $productCache = [];

    private array $inventoryCache = [];

    private array $accountCache = [];

    private array $userCache = [];

    public function __construct(private int $userId, private int $totalRows, private int $branchId) {}

    public function collection(Collection $rows)
    {
        $filteredRows = $rows->filter(function ($row) {
            return $row->filter()->isNotEmpty() && !empty($row['date']);
        });

        // Group rows by reference_no (or use a unique key if reference_no is empty)
        $groupedRows = $filteredRows->groupBy(function ($row) {
            $referenceNo = $row['reference_no'] ?? null;
            // If no reference_no, use a unique identifier based on row index
            // This ensures rows without reference_no are treated as separate sales
            return $referenceNo ?? 'unique_'.md5(serialize($row));
        });

        $processedInBatch = 0;
        foreach ($groupedRows as $referenceNo => $groupRows) {
            try {
                $processedInBatch++;
                $this->processSaleGroup($groupRows, $referenceNo);
            } catch (Throwable $th) {
                // Handle error for the entire group
                foreach ($groupRows as $row) {
                    $this->handleError($row, $th);
                }
            }
        }
        $this->processedRows += $processedInBatch;
        $this->updateProgress();
    }

    private function processSaleGroup(Collection $groupRows, string $groupKey): void
    {
        // Get the first row for sale header information
        $firstRow = $groupRows->first()->toArray();

        // Prepare sale header data from first row
        $data = [
            'invoice_no' => $firstRow['invoice_no'] ?? getNextSaleInvoiceNo(),
            'reference_no' => $firstRow['reference_no'] ?? (str_starts_with($groupKey, 'unique_') ? null : $groupKey),
            'sale_type' => $firstRow['sale_type'] ?? 'pos',
            'branch_id' => $this->branchId,
            'account_id' => $this->getAccountId($firstRow['account_id'] ?? ($firstRow['customer_name'] ?? 3)),
            'date' => excelDateConversion($firstRow['date']) ?? date('Y-m-d'),
            'due_date' => excelDateConversion($firstRow['due_date']) ?? (excelDateConversion($firstRow['date']) ?? date('Y-m-d')),
            'customer_name' => $firstRow['customer_name'] ?? null,
            'customer_mobile' => $firstRow['customer_mobile'] ?? null,
            'other_discount' => $firstRow['other_discount'] ?? 0,
            'freight' => $firstRow['freight'] ?? 0,
            'round_off' => $firstRow['round_off'] ?? 0,
            'status' => $firstRow['status'] ?? 'completed',
        ];
        // Get employee ID from first row (or use default)
        $employeeId = $this->getUserId($firstRow['employee_name'] ?? ($firstRow['employee_id'] ?? null));
        if (!$employeeId) {
            $employeeId = $this->userId;
        }

        // Prepare items from all rows in the group
        $items = [];
        $totalPaid = 0;
        $paymentMethodId = null;
        foreach ($groupRows as $row) {
            $rowData = $row->toArray();

            // Collect items from each row
            $rowItems = $this->prepareItems($rowData, $employeeId);
            $items = array_merge($items, $rowItems);

            // Aggregate payment information (use first non-zero payment found)
            if (empty($paymentMethodId) && !empty($rowData['payment_method_id'])) {
                $paymentMethodId = $rowData['payment_method_id'];
            }
            if (!empty($rowData['paid']) || !empty($rowData['amount'])) {
                $totalPaid += (float) ($rowData['paid'] ?? $rowData['amount'] ?? 0);
            }
        }

        if (empty($items)) {
            throw new Exception('No valid items found for sale with reference: ' . ($data['reference_no'] ?? 'N/A'));
        }

        $data['items'] = $items;

        // Prepare payments (aggregate from all rows or use first row)
        $payments = [];
        if ($paymentMethodId && $totalPaid > 0) {
            $payments[] = [
                'payment_method_id' => $paymentMethodId,
                'amount' => $totalPaid,
            ];
        } elseif (!empty($firstRow['payment_method_id']) && !empty($firstRow['paid'])) {
            // Fallback to first row payment if no aggregation
            $payments = $this->preparePayments($firstRow);
        }

        $data['payments'] = $payments;

        // Create sale using CreateAction
        $response = (new CreateAction())->execute($data, $this->userId);
        if (!$response['success']) {
            throw new Exception($response['message']);
        }
    }

    private function prepareItems(array $saleData, int $employeeId): array
    {
        $items = [];

        // Handle single product row
        if (!empty($saleData['product_name']) || !empty($saleData['product_id']) || !empty($saleData['barcode']) || !empty($saleData['product_code'])) {
            $item = $this->createItemFromRow($saleData, $employeeId);
            if ($item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function createItemFromRow(array $row, int $employeeId): ?array
    {
        // Get product
        $productId = null;
        if (!empty($row['product_id'])) {
            $productId = $row['product_id'];
        } elseif (!empty($row['product_code'])) {
            $productId = $this->getProductIdByCode($row['product_code']);
        } elseif (!empty($row['product_name'])) {
            $productId = $this->getProductIdByName($row['product_name']);
        } elseif (!empty($row['barcode'])) {
            $productId = $this->getProductIdByBarcode($row['barcode']);
        }

        if (!$productId) {
            throw new Exception('Product not found: ' . ($row['product_name'] ?? ($row['barcode'] ?? 'N/A')));
        }

        // Get inventory
        $inventoryId = $this->getInventoryId($productId, $this->branchId);
        if (!$inventoryId) {
            throw new Exception('Inventory not found for product ID: ' . $productId);
        }

        $quantity = $row['quantity'] ?? 1;
        $unitPrice = $row['unit_price'] ?? ($row['price'] ?? 0);
        $discount = $row['discount'] ?? 0;
        $tax = $row['tax'] ?? 0;

        // Note: gross_amount, net_amount, tax_amount, and total are calculated by database (storedAs columns)
        return [
            'inventory_id' => $inventoryId,
            'product_id' => $productId,
            'employee_id' => $employeeId,
            'assistant_id' => $this->getUserId($row['assistant_name'] ?? ($row['assistant_id'] ?? null)),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'discount' => $discount,
            'tax' => $tax,
        ];
    }

    private function preparePayments(array $saleData): array
    {
        $payments = [];

        $paymentMethodId = $saleData['payment_method_id'] ?? null;
        $amount = $saleData['paid'] ?? ($saleData['amount'] ?? 0);

        if ($paymentMethodId && $amount > 0) {
            $payments[] = [
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
            ];
        }
        return $payments;
    }

    private function getAccountId($identifier): ?int
    {
        if (empty($identifier)) {
            return null;
        }

        if (is_numeric($identifier)) {
            return (int) $identifier;
        }

        if (isset($this->accountCache[$identifier])) {
            return $this->accountCache[$identifier];
        }

        $account = Account::where('name', $identifier)->first();
        if ($account) {
            $this->accountCache[$identifier] = $account->id;

            return $account->id;
        }

        return null;
    }

    private function getUserId($identifier): ?int
    {
        if (empty($identifier)) {
            return null;
        }

        if (is_numeric($identifier)) {
            return (int) $identifier;
        }

        if (isset($this->userCache[$identifier])) {
            return $this->userCache[$identifier];
        }

        $user = User::where('name', $identifier)->first();
        if ($user) {
            $this->userCache[$identifier] = $user->id;

            return $user->id;
        }

        return null;
    }

    private function getProductIdByName(string $name): ?int
    {
        if (isset($this->productCache[$name])) {
            return $this->productCache[$name];
        }

        $product = Product::where('name', $name)->first();
        if ($product) {
            $this->productCache[$name] = $product->id;

            return $product->id;
        }

        return null;
    }

    private function getProductIdByBarcode(string $barcode): ?int
    {
        if (isset($this->productCache[$barcode])) {
            return $this->productCache[$barcode];
        }

        $product = Product::where('barcode', $barcode)->first();
        if ($product) {
            $this->productCache[$barcode] = $product->id;

            return $product->id;
        }

        return null;
    }

    private function getProductIdByCode(string $code): ?int
    {
        if (isset($this->productCache[$code])) {
            return $this->productCache[$code];
        }
        $product = Product::where('code', $code)->first();
        if ($product) {
            $this->productCache[$code] = $product->id;

            return $product->id;
        }

        return null;
    }

    private function getInventoryId(int $productId, int $branchId): ?int
    {
        $key = "{$productId}_{$branchId}";
        if (isset($this->inventoryCache[$key])) {
            return $this->inventoryCache[$key];
        }

        $inventory = Inventory::where('product_id', $productId)->where('branch_id', $branchId)->first();

        if ($inventory) {
            $this->inventoryCache[$key] = $inventory->id;

            return $inventory->id;
        }

        return null;
    }

    private function handleError($value, Throwable $th): void
    {
        $errorData = $value->toArray();
        $errorData['message'] = $th->getMessage();
        $errorData['file'] = $th->getFile();
        $errorData['line'] = $th->getLine();
        $this->errors[] = $errorData;

        Log::error('Sale import error', $errorData);
    }

    private function updateProgress(): void
    {
        $progress = ($this->processedRows / $this->totalRows) * 100;
        event(new FileImportProgress($this->userId, 'Sale', $progress));
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function __destruct()
    {
        if (!empty($this->errors)) {
            event(new FileImportCompleted($this->userId, 'Sale', $this->errors));
        }
    }
}
