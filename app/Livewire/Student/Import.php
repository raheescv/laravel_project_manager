<?php

namespace App\Livewire\Student;

use App\Exports\StudentImportIssuesExport;
use App\Exports\Templates\StudentImportTemplate;
use App\Imports\RawSheetImport;
use App\Jobs\Student\ImportStudentsJob;
use App\Support\Student\StudentImportSheet;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

/**
 * Student import wizard: upload → match columns → check rows → import.
 *
 * The sheet is read once and its rows kept in the cache. Every row is checked by
 * StudentImportSheet before anything is saved; the checked rows are cached as well
 * and only the visible page is rendered, so a large school's sheet does not ride
 * along on every round-trip. The import itself is ImportStudentsJob, which applies
 * the same check again.
 */
class Import extends Component
{
    use WithFileUploads;
    use WithPagination;

    public const PER_PAGE = 25;

    protected $paginationTheme = 'bootstrap';

    public $file;

    #[Locked]
    public int $step = 1;

    public string $duplicateStrategy = 'skip';

    #[Locked]
    public ?string $filePath = null;

    #[Locked]
    public string $fileName = '';

    #[Locked]
    public int $rowCount = 0;

    /** @var array<string, string> heading key => header as typed */
    #[Locked]
    public array $headers = [];

    /** @var array<string, string> heading key => first value under it */
    #[Locked]
    public array $samples = [];

    /** @var array<string, string> field => heading key ('' when not imported) */
    public array $mappings = [];

    /** @var array<string, string> field => heading key its header matched on its own */
    #[Locked]
    public array $guessed = [];

    /** Names this upload's cache entries. */
    #[Locked]
    public ?string $token = null;

    /** Counts from the last check; empty until the rows are checked (and again after a mapping change). */
    #[Locked]
    public array $summary = [];

    public string $rowFilter = 'all';

    public string $search = '';

    #[Locked]
    public ?string $runId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('student.import'), 403);
    }

    public function updatedFile(): void
    {
        abort_unless(auth()->user()?->can('student.import'), 403);
        $this->validate(['file' => ['required', 'file', 'max:10240', 'extensions:csv,xlsx,xls']]);

        $this->discardUpload();
        $path = $this->file->storeAs('student-imports', Str::random(40).'.'.strtolower($this->file->getClientOriginalExtension()), 'local');
        $fileName = $this->file->getClientOriginalName();
        $this->reset('file');

        try {
            [$header, $rows] = $this->readSheet($path);
        } catch (\Throwable) {
            Storage::disk('local')->delete($path);
            $this->addError('file', 'This file could not be read as a spreadsheet.');

            return;
        }

        if (! $rows) {
            Storage::disk('local')->delete($path);
            $this->addError('file', 'The sheet needs a header row and at least one student row.');

            return;
        }
        if (count($rows) > StudentImportSheet::MAX_ROWS) {
            Storage::disk('local')->delete($path);
            $this->addError('file', 'This sheet has more than '.number_format(StudentImportSheet::MAX_ROWS).' students. Split it into smaller files.');

            return;
        }

        $this->filePath = $path;
        $this->fileName = $fileName;
        $this->rowCount = count($rows);
        $this->token = Str::random(24);
        Cache::put($this->cacheKey('rows'), $rows, now()->addHours(2));

        $this->describeColumns($header, $rows);
        $this->guessed = StudentImportSheet::guessMappings($this->headers);
        $this->mappings = array_merge(array_fill_keys(StudentImportSheet::fields(), ''), $this->guessed);
        $this->summary = [];
        $this->step = 2;
    }

    public function updatedMappings(): void
    {
        $this->summary = [];
    }

    public function updatedDuplicateStrategy(): void
    {
        if (! in_array($this->duplicateStrategy, ['skip', 'update'], true)) {
            $this->duplicateStrategy = 'skip';
        }
        if ($this->step === 3 && $this->summary) {
            $this->checkRows();
        }
    }

    public function updatedRowFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function goToStep(int $step): void
    {
        $allowed = $this->step !== 4 && match ($step) {
            1 => true,
            2 => (bool) $this->filePath,
            3 => (bool) $this->summary,
            default => false,
        };

        if ($allowed) {
            $this->step = $step;
        }
    }

    public function checkRows(): void
    {
        abort_unless(auth()->user()?->can('student.import'), 403);
        if (! $this->filePath || $this->step === 4) {
            return;
        }

        $mappings = $this->validMappings();
        if (array_diff(StudentImportSheet::REQUIRED, array_keys($mappings))) {
            $this->addError('mappings', 'Choose the columns for Admission No and Student name.');
            $this->step = 2;

            return;
        }

        $sheet = new StudentImportSheet($mappings, $this->duplicateStrategy);
        $entries = $sheet->review($this->rows());
        Cache::put($this->cacheKey('review'), array_map(fn (array $entry) => Arr::except($entry, ['data']), $entries), now()->addHours(2));

        $counts = array_count_values(array_column($entries, 'status'));
        $logins = $sheet->parentLogins();
        $this->summary = [
            'rows' => count($entries),
            'new' => $counts['new'] ?? 0,
            'update' => $counts['update'] ?? 0,
            'skip' => $counts['skip'] ?? 0,
            'error' => $counts['error'] ?? 0,
            'parents_new' => $logins['new'],
            'parents_existing' => $logins['existing'],
        ];

        if (! in_array($this->rowFilter, $this->filters(), true)) {
            $this->rowFilter = 'all';
        }
        $this->resetPage();
        $this->step = 3;
    }

    public function startImport(): void
    {
        abort_unless(auth()->user()?->can('student.import'), 403);
        if ($this->step !== 3 || ! $this->summary || ! $this->filePath) {
            return;
        }
        if ($this->summary['new'] + $this->summary['update'] === 0) {
            $this->dispatch('error', ['message' => 'There are no rows to import.']);

            return;
        }

        $this->runId = (string) Str::uuid();
        ImportStudentsJob::dispatch(Auth::id(), $this->filePath, (int) Auth::user()->tenant_id, $this->duplicateStrategy, $this->validMappings(), $this->runId);

        // The job owns the file from here and deletes it when it finishes.
        Cache::forget($this->cacheKey('rows'));
        Cache::forget($this->cacheKey('review'));
        $this->filePath = null;
        $this->step = 4;
    }

    public function startOver(): void
    {
        $this->discardUpload();
        $this->reset('file', 'step', 'filePath', 'fileName', 'rowCount', 'headers', 'samples', 'mappings', 'guessed', 'token', 'summary', 'rowFilter', 'search', 'runId');
        $this->resetPage();
    }

    public function downloadTemplate()
    {
        abort_unless(auth()->user()?->can('student.import'), 403);

        return Excel::download(new StudentImportTemplate(), 'student_import_template.xlsx');
    }

    public function downloadIssues()
    {
        abort_unless(auth()->user()?->can('student.import'), 403);
        $issues = array_values(array_filter($this->entries(), fn (array $entry) => $entry['status'] === 'error'));
        if (! $issues) {
            return;
        }

        return Excel::download(new StudentImportIssuesExport($issues), 'student_import_issues.xlsx');
    }

    public function render()
    {
        $page = null;
        if ($this->step === 3 && $this->summary) {
            $rows = array_values(array_filter($this->entries(), fn (array $entry) => $this->matchesFilter($entry)));
            $lastPage = max(1, (int) ceil(count($rows) / self::PER_PAGE));
            $current = min(max(1, (int) $this->getPage()), $lastPage);
            $page = new LengthAwarePaginator(array_slice($rows, ($current - 1) * self::PER_PAGE, self::PER_PAGE), count($rows), self::PER_PAGE, $current);
        }

        return view('livewire.student.import', [
            'groups' => StudentImportSheet::GROUPS,
            'relationDefaults' => StudentImportSheet::relationDefaults($this->validMappings()),
            'mappedCount' => count($this->validMappings()),
            'unusedHeaders' => array_diff_key($this->headers, array_flip($this->validMappings())),
            'rows' => $page,
            'status' => $this->step === 4 && $this->runId ? Cache::get(ImportStudentsJob::statusKey($this->runId)) : null,
        ]);
    }

    /** @return list<string> the row filters that apply to the last check */
    public function filters(): array
    {
        return array_merge(['all'], array_values(array_filter(['new', 'update', 'skip', 'error'], fn ($status) => ($this->summary[$status] ?? 0) > 0)));
    }

    /** @return array<string, string> field => heading key, only columns that exist in this sheet */
    private function validMappings(): array
    {
        return array_filter(StudentImportSheet::cleanMappings($this->mappings), fn (string $key) => isset($this->headers[$key]));
    }

    private function matchesFilter(array $entry): bool
    {
        if ($this->rowFilter !== 'all' && $entry['status'] !== $this->rowFilter) {
            return false;
        }

        $search = mb_strtolower(trim($this->search));
        if ($search === '') {
            return true;
        }

        $haystack = implode(' ', [$entry['admission_no'], $entry['name'], ...array_map(fn ($p) => $p['name'].' '.$p['mobile'], $entry['parents'])]);

        return str_contains(mb_strtolower($haystack), $search);
    }

    /** @return list<array> the checked rows, re-checked when the cache has lost them */
    private function entries(): array
    {
        $entries = Cache::get($this->cacheKey('review'));
        if ($entries === null && $this->filePath) {
            $this->checkRows();
            $entries = Cache::get($this->cacheKey('review'));
        }

        return $entries ?? [];
    }

    /** @return array<int, array> sheet line => row (heading key => cell) */
    private function rows(): array
    {
        $rows = Cache::get($this->cacheKey('rows'));
        if ($rows === null) {
            [, $rows] = $this->readSheet($this->filePath);
            Cache::put($this->cacheKey('rows'), $rows, now()->addHours(2));
        }

        return $rows;
    }

    /**
     * Read the file the way the queued import will: the first row is the heading,
     * keyed with Maatwebsite's own heading formatter.
     *
     * @return array{0: array<int, mixed>, 1: array<int, array>} header cells, sheet line => row
     */
    private function readSheet(string $path): array
    {
        $raw = (new RawSheetImport(StudentImportSheet::MAX_ROWS + 1))->read(Storage::disk('local')->path($path));
        $header = $raw[0] ?? [];
        $keys = HeadingRowFormatter::format($header);

        $rows = [];
        foreach (array_slice($raw, 1, null, true) as $index => $cells) {
            if (! array_filter($cells, fn ($cell) => trim((string) $cell) !== '')) {
                continue;
            }
            $row = [];
            foreach ($keys as $column => $key) {
                $row[$key] = $cells[$column] ?? null;
            }
            $rows[$index + 1] = $row;
        }

        return [$header, $rows];
    }

    private function describeColumns(array $header, array $rows): void
    {
        $this->headers = [];
        $this->samples = [];
        $sampleRows = array_slice($rows, 0, 50);

        foreach (HeadingRowFormatter::format($header) as $column => $key) {
            $key = (string) $key;
            $label = trim((string) ($header[$column] ?? ''));
            $sample = '';
            foreach ($sampleRows as $row) {
                $value = $row[$key] ?? null;
                if (trim((string) $value) !== '') {
                    $sample = is_float($value) && floor($value) === $value ? sprintf('%.0f', $value) : trim((string) $value);
                    break;
                }
            }
            if (isset($this->headers[$key]) || ($label === '' && $sample === '')) {
                continue;
            }
            $this->headers[$key] = $label !== '' ? $label : 'Column '.($column + 1);
            $this->samples[$key] = Str::limit($sample, 40);
        }
    }

    private function discardUpload(): void
    {
        if ($this->filePath) {
            Storage::disk('local')->delete($this->filePath);
        }
        if ($this->token) {
            Cache::forget($this->cacheKey('rows'));
            Cache::forget($this->cacheKey('review'));
        }
    }

    private function cacheKey(string $part): string
    {
        return 'student-import:'.Auth::id().':'.$this->token.':'.$part;
    }
}
