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
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class QuickBooksVoucherImport implements WithMultipleSheets
{
    public function __construct(
        private int $userId,
        private int $totalRows,
        private int $branchId,
        private array $mappings = []
    ) {}

    public function sheets(): array
    {
        return [
            0 => new QuickBooksSheetImport($this->userId, $this->totalRows, $this->branchId, $this->mappings),
        ];
    }
}

class QuickBooksSheetImport implements ToCollection, WithBatchInserts, WithChunkReading
{
    private int $processedRows = 0;

    private array $errors = [];

    private array $accountCache = [];

    private array $columnMap = [];

    public function __construct(
        private int $userId,
        private int $totalRows,
        private int $branchId,
        private array $mappings = []
    ) {}

    public function collection(Collection $rows)
    {
        $this->buildColumnMap($rows->first());

        $transactions = $this->parseTransactions($rows);

        foreach ($transactions as $transaction) {
            try {
                $this->processTransaction($transaction);
            } catch (\Throwable $th) {
                $this->handleError($transaction, $th);
            }
            $this->processedRows += count($transaction['rows']);
            $this->updateProgress();
        }
    }

    /**
     * Build column index map from the header row.
     * Handles the duplicate "Type" header: first = transaction type, second = account type.
     */
    private function buildColumnMap(Collection $headerRow): void
    {
        $headers = $headerRow->toArray();
        $typeCount = 0;

        foreach ($headers as $index => $header) {
            $header = trim($header ?? '');
            $normalized = strtolower($header);

            $key = match ($normalized) {
                'trans #', 'trans' => 'trans_no',
                'date' => 'date',
                'num' => 'num',
                'name' => 'name',
                'memo' => 'memo',
                'account' => 'account',
                'account type' => 'account_type',
                'account category' => 'account_category',
                'debit' => 'debit',
                'credit' => 'credit',
                default => null,
            };

            if ($key) {
                $this->columnMap[$key] = $index;
            }
        }
    }

    private function col(array $row, string $key): mixed
    {
        $index = $this->columnMap[$key] ?? null;

        return $index !== null ? ($row[$index] ?? null) : null;
    }

    private function parseTransactions(Collection $rows): array
    {
        $transactions = [];
        $currentTransaction = null;

        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();

            // Skip header row
            if ($index === 0) {
                continue;
            }

            $transNo = $this->col($rowArray, 'trans_no');
            $account = trim($this->col($rowArray, 'account') ?? '');

            // Start new transaction when Trans # is present
            if (! empty($transNo) && is_numeric($transNo)) {
                if ($currentTransaction && ! empty($currentTransaction['entries'])) {
                    $transactions[] = $currentTransaction;
                }
                $currentTransaction = [
                    'trans_no' => $transNo,
                    'type' => trim($this->col($rowArray, 'type') ?? ''),
                    'entries' => [],
                    'rows' => [],
                ];
            }

            // Skip total/separator rows
            if (empty($account)) {
                if ($currentTransaction) {
                    $currentTransaction['rows'][] = $rowArray;
                }

                continue;
            }

            if ($currentTransaction !== null) {
                $currentTransaction['entries'][] = [
                    'date' => $this->col($rowArray, 'date'),
                    'num' => trim($this->col($rowArray, 'num') ?? ''),
                    'name' => trim($this->col($rowArray, 'name') ?? ''),
                    'memo' => trim($this->col($rowArray, 'memo') ?? ''),
                    'account' => $account,
                    'account_type' => trim($this->col($rowArray, 'account_type') ?? ''),
                    'account_category' => trim($this->col($rowArray, 'account_category') ?? ''),
                    'debit' => (float) ($this->col($rowArray, 'debit') ?? 0),
                    'credit' => (float) ($this->col($rowArray, 'credit') ?? 0),
                ];
                $currentTransaction['rows'][] = $rowArray;
            }
        }

        if ($currentTransaction && ! empty($currentTransaction['entries'])) {
            $transactions[] = $currentTransaction;
        }

        return $transactions;
    }

    private function processTransaction(array $transaction): void
    {
        $allEntries = collect($transaction['entries']);

        if ($allEntries->isEmpty()) {
            return;
        }

        $entries = [];
        $firstEntry = $allEntries->first();

        foreach ($allEntries as $entry) {
            $accountName = $entry['account'];
            if (empty($accountName)) {
                continue;
            }

            $accountId = $this->resolveOrCreateAccount(
                $accountName,
                $entry['account_type'],
                $entry['account_category']
            );

            $debit = $entry['debit'];
            $credit = $entry['credit'];

            if ($debit == 0 && $credit == 0) {
                continue;
            }

            $entries[] = [
                'account_id' => $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $entry['memo'] ?: null,
                'person_name' => $entry['name'] ?: null,
            ];
        }

        if (count($entries) < 2) {
            return;
        }

        $totalDebit = array_sum(array_column($entries, 'debit'));
        $totalCredit = array_sum(array_column($entries, 'credit'));

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception(
                'Debit/Credit mismatch for transaction #'.$transaction['trans_no'].
                '. Debit: '.$totalDebit.', Credit: '.$totalCredit
            );
        }

        $date = $firstEntry['date'] ? $this->parseDate($firstEntry['date']) : now()->format('Y-m-d');

        $data = [
            'branch_id' => $this->branchId,
            'date' => $date,
            'source' => 'General Voucher',
            'description' => $firstEntry['memo'] ?: ('QuickBooks: '.$transaction['type'].' #'.$transaction['trans_no']),
            'reference_number' => $firstEntry['num'] ?: null,
            'person_name' => $firstEntry['name'] ?: null,
            'remarks' => 'QuickBooks Import',
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

        $account = Account::where('name', $name)->first();

        if (! $account) {
            $categoryId = null;
            if (! empty($categoryName)) {
                $categoryId = AccountCategory::selfCreate(trim($categoryName));
            }

            $account = Account::create([
                'name' => $name,
                'account_type' => self::normalizeAccountType($accountType),
                'account_category_id' => $categoryId,
            ]);
        }

        $this->accountCache[$cacheKey] = $account->id;

        return $account->id;
    }

    public static function normalizeAccountType(?string $type): string
    {
        if (empty($type)) {
            return 'asset';
        }

        $type = strtolower(trim($type));

        $map = [
            'asset' => 'asset',
            'liability' => 'liability',
            'liabelity' => 'liability',
            'income' => 'income',
            'revenue' => 'income',
            'expense' => 'expense',
            'expenses' => 'expense',
            'equity' => 'equity',
        ];

        return $map[$type] ?? 'asset';
    }

    private function parseDate($value): string
    {
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject((int) $value)->format('Y-m-d');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return now()->format('Y-m-d');
        }
    }

    private function handleError($transaction, \Throwable $th): void
    {
        $errorData = [
            'transaction' => 'Trans #'.($transaction['trans_no'] ?? 'Unknown'),
            'message' => $th->getMessage(),
            'file' => $th->getFile(),
            'line' => $th->getLine(),
        ];

        $this->errors[] = $errorData;
        Log::error('QuickBooks import error', $errorData);
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
        return 5000;
    }

    public function __destruct()
    {
        if (! empty($this->errors)) {
            event(new FileImportCompleted($this->userId, 'GeneralVoucher', $this->errors));
        }
    }
}
