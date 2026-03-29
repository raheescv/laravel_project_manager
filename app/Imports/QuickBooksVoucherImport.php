<?php

namespace App\Imports;

use App\Actions\Journal\GeneralVoucherJournalEntryAction;
use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Models\Account;
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
            // Index 1 = "Sheet1" (the data sheet, index 0 is "QuickBooks Export Tips" which is empty)
            1 => new QuickBooksSheetImport($this->userId, $this->totalRows, $this->branchId, $this->mappings),
        ];
    }
}

class QuickBooksSheetImport implements ToCollection, WithBatchInserts, WithChunkReading
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
        $transactions = $this->parseQuickBooksTransactions($rows);

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
     * Parse QuickBooks export format into grouped transactions.
     *
     * QB structure: columns at odd indices (1,3,5,...) are empty spacers.
     * Real data columns (0-indexed): 2=Type+Num header, 3=Num, 5=Entered/Modified,
     * 7=Modified by, 9=State, 11=Date, 13=Name, 15=Memo, 17=Account, 19=Split, 21=Amount
     */
    private function parseQuickBooksTransactions(Collection $rows): array
    {
        $transactions = [];
        $currentTransaction = null;
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            // Skip the header row (row 0 contains column names like "Num", "State", etc.)
            if ($index === 0) {
                continue;
            }

            // Skip section headers like "Transactions entered or modified by Admin"
            $col2 = trim($rowArray[2] ?? '');
            $col3 = trim($rowArray[3] ?? '');
            $state = trim($rowArray[9] ?? '');
            $account = trim($rowArray[17] ?? '');
            $amount = $rowArray[21] ?? null;

            // Check if this is a transaction type header (e.g., "Bill 21857", "Payment 00011198")
            if (! empty($col2) && empty($col3) && empty($state)) {
                // Skip non-data section headers
                if (str_starts_with($col2, 'Transactions entered')) {
                    continue;
                }

                // Start new transaction group
                if ($currentTransaction && ! empty($currentTransaction['entries'])) {
                    $transactions[] = $currentTransaction;
                }
                $currentTransaction = [
                    'type_header' => $col2,
                    'entries' => [],
                    'rows' => [],
                ];

                continue;
            }

            // Skip empty/separator rows
            if (empty($account) && $amount === null && empty($col3)) {
                continue;
            }

            // This is a data row — collect it
            if (! empty($account) && $amount !== null && $currentTransaction !== null) {
                $currentTransaction['entries'][] = [
                    'num' => $col3,
                    'date' => $rowArray[11] ?? null,
                    'name' => trim($rowArray[13] ?? ''),
                    'memo' => trim($rowArray[15] ?? ''),
                    'account' => $account,
                    'split' => trim($rowArray[19] ?? ''),
                    'amount' => (float) $amount,
                    'state' => $state,
                ];
                $currentTransaction['rows'][] = $rowArray;
            }
        }
        // Don't forget the last transaction
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

        // Build journal entries: each row has Account + Amount
        // Positive amount = Debit, Negative amount = Credit (QB convention)
        $entries = [];
        $firstEntry = $allEntries->first();

        foreach ($allEntries as $entry) {
            $accountName = $entry['account'];
            if (empty($accountName)) {
                continue;
            }

            $accountId = $this->resolveOrCreateAccount($accountName);
            $amount = $entry['amount'];

            if ($amount == 0) {
                continue;
            }

            $entries[] = [
                'account_id' => $accountId,
                'debit' => $amount > 0 ? abs($amount) : 0,
                'credit' => $amount < 0 ? abs($amount) : 0,
                'description' => $entry['memo'] ?: null,
                'person_name' => $entry['name'] ?: null,
            ];
        }
        if (count($entries) < 2) {
            return; // Skip incomplete transactions silently
        }

        // Validate debit = credit
        $totalDebit = array_sum(array_column($entries, 'debit'));
        $totalCredit = array_sum(array_column($entries, 'credit'));

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new Exception(
                'Debit/Credit mismatch for QB transaction "'.$transaction['type_header'].
                '". Debit: '.$totalDebit.', Credit: '.$totalCredit
            );
        }

        $date = $firstEntry['date'] ? $this->parseDate($firstEntry['date']) : now()->format('Y-m-d');

        $data = [
            'branch_id' => $this->branchId,
            'date' => $date,
            'source' => 'General Voucher',
            'description' => $firstEntry['memo'] ?: ('QuickBooks: '.$transaction['type_header']),
            'reference_number' => $firstEntry['num'] ?: null,
            'person_name' => $firstEntry['name'] ?: null,
            'remarks' => 'QuickBooks Import',
            'entries' => $entries,
        ];

        $response = (new GeneralVoucherJournalEntryAction())->execute($this->userId, $data);
        if (! $response['success']) {
            info($response);
            throw new Exception($response['message']);
        }
    }

    private function resolveOrCreateAccount(string $name): int
    {
        $cacheKey = strtolower(trim($name));

        if (isset($this->accountCache[$cacheKey])) {
            return $this->accountCache[$cacheKey];
        }

        $account = Account::where('name', $name)->first();

        if (! $account) {
            $account = Account::create([
                'name' => $name,
                'account_type' => 'Account Head',
                'account_category_id' => null,
            ]);
        }

        $this->accountCache[$cacheKey] = $account->id;

        return $account->id;
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
            'transaction' => $transaction['type_header'] ?? 'Unknown',
            'message' => $th->getMessage(),
            'file' => $th->getFile(),
            'line' => $th->getLine(),
        ];

        $this->errors[] = $errorData;
        Log::error('QuickBooks Voucher import error', $errorData);
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
        // Use large chunk to avoid splitting QB transaction groups across chunks
        return 5000;
    }

    public function __destruct()
    {
        if (! empty($this->errors)) {
            event(new FileImportCompleted($this->userId, 'GeneralVoucher', $this->errors));
        }
    }
}
