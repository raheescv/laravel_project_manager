<?php

namespace App\Livewire\Settings\ChecklistItem;

use App\Exports\Templates\ChecklistItemImportTemplate;
use App\Jobs\ImportChecklistItemsJob;
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
        'name' => 'Item Name (*)',
        'category' => 'Category / Room',
        'property_type' => 'Property Type',
        'sort_order' => 'Sort Order',
        'is_active' => 'Active (yes/no)',
    ];

    public function mount()
    {
        abort_unless(auth()->user()?->can('rent out checklist item.import'), 403);
    }

    private function getHeaderAliases(): array
    {
        return [
            'name' => ['name', 'item', 'item_name', 'item name', 'checklist item', 'checklistitem', 'checklist_item', 'title', 'description'],
            'category' => ['category', 'room', 'section', 'group', 'area', 'checklist category', 'item category'],
            'property_type' => ['property_type', 'propertytype', 'property type', 'property', 'unit type', 'unittype', 'type'],
            'sort_order' => ['sort_order', 'sortorder', 'sort order', 'sort', 'order', 'sequence', 'sl', 'sl no', 'position'],
            'is_active' => ['is_active', 'isactive', 'is active', 'active', 'status', 'enabled'],
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
        return Excel::download(new ChecklistItemImportTemplate(), 'checklist_item_import_template.xlsx');
    }

    public function save()
    {
        abort_unless(auth()->user()?->can('rent out checklist item.import'), 403);
        $this->validate([
            'mappings.name' => 'required',
        ], [
            'mappings.name.required' => 'The Item Name field must be mapped.',
        ]);

        ImportChecklistItemsJob::dispatch(
            Auth::id(),
            $this->filePath,
            session('tenant_id'),
            $this->mappings,
            $this->duplicateStrategy
        );

        $this->jobDispatchedAt = now()->toDateTimeString();
        $this->importStatus = 'processing';
        $this->dispatch('success', ['message' => 'Checklist item import started in background']);
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
            ->where('payload', 'like', '%ImportChecklistItemsJob%')
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
            ->where('payload', 'like', '%ImportChecklistItemsJob%')
            ->exists();

        if (! $pendingJob) {
            $this->importStatus = 'completed';
            $this->dispatch('import-completed');
        }
    }

    public function render()
    {
        return view('livewire.settings.checklist-item.import');
    }
}
