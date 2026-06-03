<?php

namespace App\Imports;

use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\CustomerType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AccountImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private int $processedRows = 0;

    private array $errors = [];

    private array $categoryCache = [];

    private array $customerTypeCache = [];

    public function __construct(
        private int $userId,
        private int $totalRows,
        private ?int $tenantId = null,
        private array $mappings = [],
        private string $duplicateStrategy = 'skip'
    ) {}

    public function collection(Collection $rows)
    {
        $nameHeader = $this->mappings['name'] ?? 'name';

        $filteredRows = $rows->filter(function ($row) use ($nameHeader) {
            return $row->filter()->isNotEmpty() && ! empty($row[$nameHeader]);
        });

        $processedInBatch = 0;

        foreach ($filteredRows as $value) {
            try {
                $processedInBatch++;
                $this->processAccountRow($value);
            } catch (\Throwable $th) {
                $this->handleError($value, $th);
            }
        }

        $this->processedRows += $processedInBatch;
        $this->updateProgress();
    }

    private function getMappedValue($row, string $field): mixed
    {
        $excelHeader = $this->mappings[$field] ?? $field;

        return $row[$excelHeader] ?? null;
    }

    private function processAccountRow($value): void
    {
        $name = trim((string) $this->getMappedValue($value, 'name'));
        if ($name === '') {
            return;
        }

        $accountType = $this->normalizeAccountType($this->getMappedValue($value, 'account_type'));
        $mobile = $this->trimOrNull($this->getMappedValue($value, 'mobile'));

        $data = [
            'tenant_id' => $this->tenantId,
            'account_type' => $accountType,
            'name' => $name,
            'alias_name' => $this->trimOrNull($this->getMappedValue($value, 'alias_name')),
            'mobile' => $mobile,
            'whatsapp_mobile' => $this->trimOrNull($this->getMappedValue($value, 'whatsapp_mobile')),
            'email' => $this->trimOrNull($this->getMappedValue($value, 'email')),
            'model' => $this->normalizeModel($this->getMappedValue($value, 'model')),
            'place' => $this->trimOrNull($this->getMappedValue($value, 'place')),
            'company' => $this->trimOrNull($this->getMappedValue($value, 'company')),
            'description' => $this->trimOrNull($this->getMappedValue($value, 'description')),
            'id_no' => $this->trimOrNull($this->getMappedValue($value, 'id_no')),
            'nationality' => $this->trimOrNull($this->getMappedValue($value, 'nationality')),
            'second_reference_no' => $this->trimOrNull($this->getMappedValue($value, 'second_reference_no')),
            'opening_debit' => $this->toDecimal($this->getMappedValue($value, 'opening_debit')),
            'opening_credit' => $this->toDecimal($this->getMappedValue($value, 'opening_credit')),
            'credit_period_days' => $this->toIntOrNull($this->getMappedValue($value, 'credit_period_days')),
            'dob' => $this->parseDate($this->getMappedValue($value, 'dob')),
        ];

        $categoryName = $this->trimOrNull($this->getMappedValue($value, 'account_category'));
        if ($categoryName) {
            $data['account_category_id'] = $this->resolveCategory($categoryName);
        }

        $customerTypeName = $this->trimOrNull($this->getMappedValue($value, 'customer_type'));
        if ($customerTypeName) {
            $data['customer_type_id'] = $this->resolveCustomerType($customerTypeName);
        }

        $existing = Account::where('tenant_id', $this->tenantId)
            ->where('account_type', $accountType)
            ->where('name', $name)
            ->where('mobile', $mobile)
            ->first();

        if ($existing) {
            if ($this->duplicateStrategy === 'update') {
                $existing->update(array_filter($data, fn ($v) => $v !== null && $v !== ''));

                return;
            }

            return; // skip
        }

        Account::create(array_filter($data, fn ($v) => $v !== null));
    }

    private function normalizeAccountType($value): string
    {
        $value = strtolower(trim((string) $value));
        $valid = array_keys(accountTypes());

        if (in_array($value, $valid, true)) {
            return $value;
        }

        $aliases = [
            'assets' => 'asset',
            'a' => 'asset',
            'liabilities' => 'liability',
            'liab' => 'liability',
            'l' => 'liability',
            'revenue' => 'income',
            'revenues' => 'income',
            'sales' => 'income',
            'expenses' => 'expense',
            'cost' => 'expense',
            'costs' => 'expense',
            'capital' => 'equity',
        ];

        return $aliases[$value] ?? 'asset';
    }

    private function normalizeModel($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $lower = strtolower($value);
        if (in_array($lower, ['customer', 'customers', 'client'], true)) {
            return 'Customer';
        }
        if (in_array($lower, ['vendor', 'vendors', 'supplier', 'suppliers'], true)) {
            return 'Vendor';
        }

        return ucfirst($lower);
    }

    private function resolveCategory(string $name): ?int
    {
        $key = strtolower($name);
        if (isset($this->categoryCache[$key])) {
            return $this->categoryCache[$key];
        }

        $id = AccountCategory::selfCreate($name);
        $this->categoryCache[$key] = $id;

        return $id;
    }

    private function resolveCustomerType(string $name): ?int
    {
        $key = strtolower($name);
        if (isset($this->customerTypeCache[$key])) {
            return $this->customerTypeCache[$key];
        }

        $type = CustomerType::firstOrCreate(
            ['tenant_id' => $this->tenantId, 'name' => $name],
            ['discount_percentage' => 0]
        );

        $this->customerTypeCache[$key] = $type->id;

        return $type->id;
    }

    private function trimOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function toDecimal($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) preg_replace('/[^\d.\-]/', '', (string) $value);
    }

    private function toIntOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function handleError($value, \Throwable $th): void
    {
        $errorData = $value instanceof Collection ? $value->toArray() : (array) $value;
        $errorData['message'] = $th->getMessage();
        $errorData['file'] = $th->getFile();
        $errorData['line'] = $th->getLine();

        $this->errors[] = $errorData;

        Log::error('Account import error', $errorData);
    }

    private function updateProgress(): void
    {
        $progress = min(($this->processedRows / max($this->totalRows, 1)) * 100, 100);
        event(new FileImportProgress($this->userId, 'Account', $progress));
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
            event(new FileImportCompleted($this->userId, 'Account', $this->errors));
        }
    }
}
