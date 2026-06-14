<?php

namespace App\Livewire\Account;

use App\Exports\Templates\AccountImportTemplate;
use App\Jobs\ImportAccountsJob;
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

    public $duplicateStrategy = 'skip'; // skip | update

    public $availableFields = [
        'name' => 'Account Head Name (*)',
        'account_type' => 'Account Type (*)',
        'account_category' => 'Account Category',
        'customer_type' => 'Customer Type',
        'model' => 'Model (Customer / Vendor)',
        'alias_name' => 'Alias Name',
        'mobile' => 'Mobile',
        'whatsapp_mobile' => 'WhatsApp Mobile',
        'email' => 'Email',
        'opening_debit' => 'Opening Debit',
        'opening_credit' => 'Opening Credit',
        'credit_period_days' => 'Credit Period (days)',
        'place' => 'Place',
        'company' => 'Company',
        'dob' => 'Date of Birth',
        'id_no' => 'ID Number',
        'nationality' => 'Nationality',
        'second_reference_no' => 'Second Reference No',
        'description' => 'Description',
    ];

    private function getHeaderAliases(): array
    {
        return [
            'name' => ['name', 'account', 'account_name', 'account name', 'accounthead', 'account head', 'ledger', 'ledger name', 'ledgername', 'party', 'party name', 'partyname'],
            'account_type' => ['account_type', 'accounttype', 'account type', 'type', 'head type', 'ledger type'],
            'account_category' => ['account_category', 'accountcategory', 'account category', 'category', 'group', 'account group', 'accountgroup', 'under'],
            'customer_type' => ['customer_type', 'customertype', 'customer type', 'client type'],
            'model' => ['model', 'kind', 'party type', 'partytype'],
            'alias_name' => ['alias', 'alias_name', 'aliasname', 'alias name', 'short name', 'shortname', 'nickname'],
            'mobile' => ['mobile', 'phone', 'contact', 'contact_no', 'contact no', 'phone number', 'phonenumber', 'mobile no', 'mobileno'],
            'whatsapp_mobile' => ['whatsapp', 'whatsapp_mobile', 'whatsappmobile', 'whatsapp mobile', 'whatsapp number', 'wa'],
            'email' => ['email', 'email_id', 'email id', 'emailid', 'mail'],
            'opening_debit' => ['opening_debit', 'openingdebit', 'opening debit', 'opening dr', 'openingdr', 'debit opening'],
            'opening_credit' => ['opening_credit', 'openingcredit', 'opening credit', 'opening cr', 'openingcr', 'credit opening'],
            'credit_period_days' => ['credit_period_days', 'creditperioddays', 'credit period days', 'credit period', 'creditperiod', 'credit days', 'creditdays'],
            'place' => ['place', 'city', 'location', 'address', 'town'],
            'company' => ['company', 'company name', 'companyname', 'organization', 'organisation', 'firm'],
            'dob' => ['dob', 'date of birth', 'dateofbirth', 'birthday', 'birth date', 'birthdate'],
            'id_no' => ['id_no', 'idno', 'id no', 'id number', 'idnumber', 'identification', 'national id', 'trn', 'tax number'],
            'nationality' => ['nationality', 'country', 'nation'],
            'second_reference_no' => ['second_reference_no', 'secondreferenceno', 'second reference no', 'second ref', 'secondary reference', 'alt ref', 'alt reference'],
            'description' => ['description', 'remarks', 'note', 'notes', 'memo', 'comment', 'comments'],
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

        $headings = (new HeadingRowImport())->toArray(Storage::disk('public')->path($this->filePath));
        $this->headers = $headings[0][0] ?? [];

        $aliases = $this->getHeaderAliases();
        $this->mappings = [];

        foreach ($this->availableFields as $field => $label) {
            $allowed = $aliases[$field] ?? [$this->normalizeHeader($field)];
            $normalizedAllowed = array_map(fn ($a) => $this->normalizeHeader($a), $allowed);
            foreach ($this->headers as $header) {
                $normalizedHeader = $this->normalizeHeader((string) $header);
                if (in_array($normalizedHeader, $normalizedAllowed, true)) {
                    $this->mappings[$field] = $header;
                    break;
                }
            }
        }

        $this->step = 2;
        $this->loadPreview();
    }

    public function loadPreview()
    {
        $filePath = Storage::disk('public')->path($this->filePath);
        $this->previewData = $this->readFirstRows($filePath, 0, 10);
    }

    /**
     * Read only the first N data rows from a specific sheet using PhpSpreadsheet directly.
     * Avoids loading the entire file into memory.
     */
    private function readFirstRows(string $filePath, int $sheetIndex = 0, int $maxRows = 10): array
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
        return Excel::download(new AccountImportTemplate(), 'account_import_template.xlsx');
    }

    public function save()
    {
        abort_unless(auth()->user()?->can('account.create'), 403);
        $this->validate([
            'mappings.name' => 'required',
            'mappings.account_type' => 'required',
        ], [
            'mappings.name.required' => 'The Account Head Name field must be mapped.',
            'mappings.account_type.required' => 'The Account Type field must be mapped.',
        ]);

        ImportAccountsJob::dispatch(
            Auth::id(),
            $this->filePath,
            session('tenant_id'),
            $this->mappings,
            $this->duplicateStrategy
        );

        $this->jobDispatchedAt = now()->toDateTimeString();
        $this->importStatus = 'processing';
        $this->dispatch('success', ['message' => 'Account import started in background']);
        $this->step = 4;
    }

    /**
     * Poll for job status — fallback when Pusher is unavailable.
     */
    public function checkJobStatus()
    {
        if ($this->importStatus !== 'processing' || ! $this->jobDispatchedAt) {
            return;
        }

        $failedJob = DB::table('failed_jobs')
            ->where('payload', 'like', '%ImportAccountsJob%')
            ->where('failed_at', '>=', $this->jobDispatchedAt)
            ->latest('failed_at')
            ->first();

        if ($failedJob) {
            $this->importStatus = 'failed';
            $exception = $failedJob->exception ?? '';
            $errorMessage = 'Import failed.';
            if (preg_match('/^[^:]+:\s*(.+?)(?:\s+in\s+\/|$)/m', $exception, $matches)) {
                $errorMessage = $matches[1];
            }
            $this->importError = $errorMessage;
            $this->dispatch('import-failed', ['message' => $errorMessage]);

            return;
        }

        $pendingJob = DB::table('jobs')
            ->where('payload', 'like', '%ImportAccountsJob%')
            ->exists();

        if (! $pendingJob) {
            $this->importStatus = 'completed';
            $this->dispatch('import-completed');
        }
    }

    public function render()
    {
        return view('livewire.account.import');
    }
}
