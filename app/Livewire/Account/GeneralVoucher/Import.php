<?php

namespace App\Livewire\Account\GeneralVoucher;

use App\Exports\Templates\GeneralVoucherImportTemplate;
use App\Jobs\ImportGeneralVoucherJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class Import extends Component
{
    use WithFileUploads;

    public $file;

    public $step = 1;

    public $headers = [];

    public $mappings = [];

    public $previewData = [];

    public $filePath;

    public $jobDispatchedAt;

    public $importStatus = 'idle'; // idle, processing, completed, failed

    public $importError = '';

    public $importFormat = 'normal'; // normal, quickbooks

    public $availableFields = [
        'date' => 'Date (*)',
        'account_name' => 'Account Name (*)',
        'account_type' => 'Account Type',
        'account_category' => 'Account Category',
        'debit' => 'Debit Amount (*)',
        'credit' => 'Credit Amount (*)',
        'description' => 'Description',
        'reference_number' => 'Reference Number',
        'person_name' => 'Person Name',
        'remarks' => 'Remarks',
    ];

    private function getHeaderAliases(): array
    {
        return [
            'date' => ['date', 'voucherdate', 'voucher date', 'journal date', 'journaldate', 'transaction date', 'transactiondate'],
            'account_name' => ['account_name', 'accountname', 'account name', 'account', 'account head', 'accounthead', 'ledger', 'ledger name', 'ledgername'],
            'account_type' => ['account_type', 'accounttype', 'account type', 'type'],
            'account_category' => ['account_category', 'accountcategory', 'account category', 'category', 'group', 'account group', 'accountgroup'],
            'debit' => ['debit', 'debit amount', 'debitamount', 'dr', 'dr amount'],
            'credit' => ['credit', 'credit amount', 'creditamount', 'cr', 'cr amount'],
            'description' => ['description', 'narration', 'particulars', 'detail', 'details'],
            'reference_number' => ['reference_number', 'referencenumber', 'reference number', 'ref', 'ref no', 'refno', 'voucher no', 'voucherno', 'voucher number', 'vouchernumber'],
            'person_name' => ['person_name', 'personname', 'person name', 'person', 'name', 'party', 'party name', 'partyname'],
            'remarks' => ['remarks', 'remark', 'note', 'notes', 'memo'],
        ];
    }

    private function normalizeHeader(string $value): string
    {
        return strtolower(str_replace(['_', ' ', '-'], '', $value));
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        $this->filePath = $this->file->store('temp-imports', 'public');

        if ($this->importFormat === 'quickbooks') {
            $this->loadQuickBooksPreview();
            $this->step = 3; // Skip mapping step for QuickBooks
        } else {
            $headings = (new HeadingRowImport())->toArray(Storage::disk('public')->path($this->filePath));
            $this->headers = $headings[0][0] ?? [];

            $aliases = $this->getHeaderAliases();

            foreach ($this->availableFields as $field => $label) {
                $allowed = $aliases[$field] ?? [$this->normalizeHeader($field)];
                $normalizedAllowed = array_map(fn ($a) => $this->normalizeHeader($a), $allowed);
                foreach ($this->headers as $header) {
                    $normalizedHeader = $this->normalizeHeader($header);
                    if (in_array($normalizedHeader, $normalizedAllowed, true)) {
                        $this->mappings[$field] = $header;
                        break;
                    }
                }
            }

            $this->step = 2;
            $this->loadPreview();
        }
    }

    public function loadPreview()
    {
        $filePath = Storage::disk('public')->path($this->filePath);
        $this->previewData = $this->readFirstRows($filePath, 0, 10);
    }

    public function loadQuickBooksPreview()
    {
        $filePath = Storage::disk('public')->path($this->filePath);
        $rawRows = $this->readFirstRows($filePath, 0, 500, true);

        if (empty($rawRows)) {
            $this->previewData = [];

            return;
        }

        $headerRow = $rawRows[0] ?? [];
        $dataRows = array_slice($rawRows, 1);

        // Build column map from headers (handles duplicate "Type" column)
        $columnMap = $this->buildQuickBooksColumnMap($headerRow);

        $preview = [];
        foreach ($dataRows as $row) {
            $account = trim($row[$columnMap['account'] ?? -1] ?? '');

            if (! empty($account)) {
                $preview[] = [
                    'trans_no' => trim($row[$columnMap['trans_no'] ?? -1] ?? ''),
                    'type' => trim($row[$columnMap['type'] ?? -1] ?? ''),
                    'date' => $row[$columnMap['date'] ?? -1] ?? '',
                    'num' => trim($row[$columnMap['num'] ?? -1] ?? ''),
                    'name' => trim($row[$columnMap['name'] ?? -1] ?? ''),
                    'memo' => trim($row[$columnMap['memo'] ?? -1] ?? ''),
                    'account' => $account,
                    'account_type' => trim($row[$columnMap['account_type'] ?? -1] ?? ''),
                    'account_category' => trim($row[$columnMap['account_category'] ?? -1] ?? ''),
                    'debit' => (float) ($row[$columnMap['debit'] ?? -1] ?? 0),
                    'credit' => (float) ($row[$columnMap['credit'] ?? -1] ?? 0),
                ];
                if (count($preview) >= 15) {
                    break;
                }
            }
        }

        $this->previewData = $preview;
        $this->headers = ['trans_no', 'type', 'date', 'num', 'name', 'memo', 'account', 'account_type', 'account_category', 'debit', 'credit'];
    }

    private function buildQuickBooksColumnMap(array $headerRow): array
    {
        $map = [];
        $typeCount = 0;

        foreach ($headerRow as $index => $header) {
            $normalized = strtolower(trim($header ?? ''));

            if ($normalized === 'type') {
                $typeCount++;
                $map[$typeCount === 1 ? 'type' : 'account_type'] = $index;

                continue;
            }

            $key = match ($normalized) {
                'trans #', 'trans' => 'trans_no',
                'date' => 'date',
                'num' => 'num',
                'name' => 'name',
                'memo' => 'memo',
                'account' => 'account',
                'account category' => 'account_category',
                'debit' => 'debit',
                'credit' => 'credit',
                default => null,
            };

            if ($key) {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    /**
     * Read only the first N data rows from a specific sheet using PhpSpreadsheet directly.
     * Avoids loading the entire file into memory.
     */
    private function readFirstRows(string $filePath, int $sheetIndex = 0, int $maxRows = 10, bool $includeHeader = false): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $readerType = match ($extension) {
            'csv' => 'Csv',
            'xls' => 'Xls',
            default => 'Xlsx',
        };

        $readerClass = '\\PhpOffice\\PhpSpreadsheet\\Reader\\'.$readerType;
        $reader = new $readerClass();

        if ($readerType !== 'Csv') {
            $reader->setReadDataOnly(true);
        }

        // Use a read filter to only load first N+1 rows (header + data)
        $reader->setReadFilter(new class($maxRows + 1) implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
        {
            private int $maxRow;

            public function __construct(int $maxRow)
            {
                $this->maxRow = $maxRow;
            }

            public function readCell($columnAddress, $row, $worksheetName = '')
            {
                return $row <= $this->maxRow;
            }
        });

        try {
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $rows = $sheet->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if ($includeHeader) {
                return array_slice($rows, 0, $maxRows + 1);
            }

            // Skip header row, return data rows
            return array_slice($rows, 1, $maxRows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function goToStep($step)
    {
        $this->step = $step;
    }

    public function sample()
    {
        return Excel::download(new GeneralVoucherImportTemplate(), 'general_voucher_import_template.xlsx');
    }

    public function save()
    {
        if ($this->importFormat !== 'quickbooks') {
            $this->validate([
                'mappings.account_name' => 'required',
                'mappings.debit' => 'required',
                'mappings.credit' => 'required',
            ], [
                'mappings.account_name.required' => 'The Account Name field must be mapped.',
                'mappings.debit.required' => 'The Debit Amount field must be mapped.',
                'mappings.credit.required' => 'The Credit Amount field must be mapped.',
            ]);
        }

        ImportGeneralVoucherJob::dispatch(
            Auth::id(),
            $this->filePath,
            session('branch_id'),
            session('tenant_id'),
            $this->mappings,
            $this->importFormat
        );

        $this->jobDispatchedAt = now()->toDateTimeString();
        $this->importStatus = 'processing';
        $this->dispatch('success', ['message' => 'General Voucher import started in background']);
        $this->step = 4;
    }

    /**
     * Poll for job status - called by Livewire wire:poll when on step 4.
     * This is the fallback when Pusher is unavailable.
     */
    public function checkJobStatus()
    {
        if ($this->importStatus !== 'processing' || ! $this->jobDispatchedAt) {
            return;
        }

        // Check if the job has failed
        $failedJob = DB::table('failed_jobs')
            ->where('payload', 'like', '%ImportGeneralVoucherJob%')
            ->where('failed_at', '>=', $this->jobDispatchedAt)
            ->latest('failed_at')
            ->first();

        if ($failedJob) {
            $this->importStatus = 'failed';
            // Extract the error message from the exception
            $exception = $failedJob->exception ?? '';
            $errorMessage = 'Import failed.';
            if (preg_match('/^[^:]+:\s*(.+?)(?:\s+in\s+\/|$)/m', $exception, $matches)) {
                $errorMessage = $matches[1];
            }
            $this->importError = $errorMessage;
            $this->dispatch('import-failed', ['message' => $errorMessage]);

            return;
        }

        // Check if the job is still in the queue
        $pendingJob = DB::table('jobs')
            ->where('payload', 'like', '%ImportGeneralVoucherJob%')
            ->exists();

        if (! $pendingJob) {
            // Job is not in queue and not failed = completed
            $this->importStatus = 'completed';
            $this->dispatch('import-completed');
        }
    }

    public function render()
    {
        return view('livewire.account.general-voucher.import');
    }
}
