<?php

namespace App\Imports;

use App\Actions\Journal\GeneralVoucherJournalEntryAction;
use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Account;
use App\Models\AccountCategory;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GeneralVoucherImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private int $processedRows = 0;

    private array $errors = [];

    private array $accountCache = [];

    public function __construct(
        private int $userId,
        private int $totalRows,
        private int $branchId,
        private array $mappings = []
    ) {}

    public function collection(Collection $rows)
    {
        // Group rows by reference_number (or date+description combo) to create vouchers
        $groupedVouchers = $this->groupRowsIntoVouchers($rows);

        foreach ($groupedVouchers as $voucherKey => $voucherRows) {
            try {
                $this->processVoucher($voucherRows);
            } catch (\Throwable $th) {
                $this->handleError($voucherRows->first(), $th);
            }
            $this->processedRows += $voucherRows->count();
            $this->updateProgress();
        }
    }

    private function getMappedValue($row, string $field): mixed
    {
        $excelHeader = $this->mappings[$field] ?? $field;

        return $row[$excelHeader] ?? null;
    }

    private function groupRowsIntoVouchers(Collection $rows): Collection
    {
        $filteredRows = $rows->filter(function ($row) {
            $accountName = $this->getMappedValue($row, 'account_name');

            return $row->filter()->isNotEmpty() && ! empty($accountName);
        });

        // Group by reference_number if available, otherwise by date + description
        return $filteredRows->groupBy(function ($row) {
            $refNumber = $this->getMappedValue($row, 'reference_number');
            if (! empty($refNumber)) {
                return 'ref_'.trim($refNumber);
            }

            $date = $this->getMappedValue($row, 'date');
            $description = $this->getMappedValue($row, 'description');

            return 'auto_'.($date ?? 'nodate').'_'.($description ?? 'nodesc');
        });
    }

    private function processVoucher(Collection $voucherRows): void
    {
        $firstRow = $voucherRows->first();

        $entries = [];
        foreach ($voucherRows as $row) {
            $accountName = trim($this->getMappedValue($row, 'account_name') ?? '');
            if (empty($accountName)) {
                continue;
            }

            $accountId = $this->resolveOrCreateAccount(
                $accountName,
                $this->getMappedValue($row, 'account_type'),
                $this->getMappedValue($row, 'account_category')
            );

            $debit = (float) ($this->getMappedValue($row, 'debit') ?? 0);
            $credit = (float) ($this->getMappedValue($row, 'credit') ?? 0);

            if ($debit == 0 && $credit == 0) {
                continue;
            }

            $entries[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $this->getMappedValue($row, 'description') ?? null,
                'person_name' => $this->getMappedValue($row, 'person_name') ?? null,
            ];
        }

        if (count($entries) < 2) {
            throw new Exception('A voucher must have at least 2 entries. Reference: '.($this->getMappedValue($firstRow, 'reference_number') ?? 'N/A'));
        }

        // Validate debit = credit
        $totalDebit = array_sum(array_column($entries, 'debit'));
        $totalCredit = array_sum(array_column($entries, 'credit'));

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception(
                'Debit/Credit mismatch for voucher. Reference: '.($this->getMappedValue($firstRow, 'reference_number') ?? 'N/A').
                ". Debit: {$totalDebit}, Credit: {$totalCredit}"
            );
        }

        $date = $this->getMappedValue($firstRow, 'date');
        if ($date) {
            $date = $this->parseDate($date);
        } else {
            $date = now()->format('Y-m-d');
        }

        $data = [
            'branch_id' => $this->branchId,
            'date' => $date,
            'source' => 'General Voucher',
            'description' => $this->getMappedValue($firstRow, 'description') ?? 'Imported General Voucher',
            'reference_number' => $this->getMappedValue($firstRow, 'reference_number') ?? null,
            'person_name' => $this->getMappedValue($firstRow, 'person_name') ?? null,
            'remarks' => $this->getMappedValue($firstRow, 'remarks') ?? 'Bulk Import',
            'entries' => $entries,
        ];

        $response = (new GeneralVoucherJournalEntryAction())->execute($this->userId, $data);

        if (! $response['success']) {
            throw new Exception($response['message']);
        }
    }

    private function resolveOrCreateAccount(string $name, ?string $accountType = null, ?string $categoryName = null): int
    {
        $cacheKey = strtolower(trim($name));

        if (isset($this->accountCache[$cacheKey])) {
            return $this->accountCache[$cacheKey];
        }

        // Try to find existing account
        $account = Account::where('name', $name)->first();

        if (! $account) {
            // Auto-create the account
            $categoryId = null;
            if (! empty($categoryName)) {
                $categoryId = AccountCategory::selfCreate(trim($categoryName));
            }

            $account = Account::create([
                'name' => $name,
                'account_type' => ! empty($accountType) ? $accountType : 'Account Head',
                'account_category_id' => $categoryId,
            ]);
        }

        $this->accountCache[$cacheKey] = $account->id;

        return $account->id;
    }

    private function parseDate($value): string
    {
        if (is_numeric($value)) {
            // Excel serial date
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((int) $value)->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return now()->format('Y-m-d');
        }
    }

    private function handleError($value, \Throwable $th): void
    {
        $errorData = $value instanceof Collection ? $value->toArray() : (array) $value;
        $errorData['message'] = $th->getMessage();
        $errorData['file'] = $th->getFile();
        $errorData['line'] = $th->getLine();

        $this->errors[] = $errorData;

        Log::error('General Voucher import error', $errorData);
    }

    private function updateProgress(): void
    {
        $progress = min(($this->processedRows / max($this->totalRows, 1)) * 100, 100);
        event(new FileImportProgress($this->userId, 'GeneralVoucher', $progress));
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
            event(new FileImportCompleted($this->userId, 'GeneralVoucher', $this->errors));
        }
    }
}
