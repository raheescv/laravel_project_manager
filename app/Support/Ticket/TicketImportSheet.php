<?php

namespace App\Support\Ticket;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Reads a ticket sheet, matches its columns to ticket fields and checks every
 * row. The review screen and the import itself both go through review(), so
 * a row the screen shows as ready is exactly a row that gets saved.
 */
class TicketImportSheet
{
    public const MAX_ROWS = 1000;

    public const CACHE_TTL_MINUTES = 120;

    /** Rows looked at when finding the header, so a title or notes above the table are stepped over. */
    public const HEADER_SCAN_ROWS = 10;

    /**
     * @return array<string, array{label: string, required: bool, hint: string}>
     */
    public static function fields(): array
    {
        return [
            'title' => ['label' => 'Title', 'required' => true, 'hint' => 'Short summary of the issue'],
            'description' => ['label' => 'Description', 'required' => false, 'hint' => 'Full details'],
            'status' => ['label' => 'Status', 'required' => false, 'hint' => 'Open, In Progress, Resolved or Closed'],
            'group' => ['label' => 'Group', 'required' => false, 'hint' => 'Any text — e.g. Sales, Inventory'],
            'created_at' => ['label' => 'Created date', 'required' => false, 'hint' => 'Keeps the original date; blank = today'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function aliases(): array
    {
        return [
            'title' => ['title', 'subject', 'summary', 'issue', 'issues', 'issue description', 'problem title', 'task', 'ticket', 'ticket title', 'name', 'heading'],
            'description' => ['description', 'details', 'detail', 'body', 'notes', 'note', 'remarks', 'message', 'problem'],
            'status' => ['status', 'state', 'stage', 'ticket status', 'progress'],
            'group' => ['group', 'category', 'module', 'team', 'department', 'type', 'section', 'area', 'project', 'label'],
            'created_at' => ['created at', 'created', 'created date', 'date', 'reported on', 'reported date', 'opened', 'opened on', 'raised on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function statusAliases(): array
    {
        return [
            'open' => Ticket::STATUS_OPEN, 'new' => Ticket::STATUS_OPEN, 'todo' => Ticket::STATUS_OPEN, 'to do' => Ticket::STATUS_OPEN, 'pending' => Ticket::STATUS_OPEN, 'reopened' => Ticket::STATUS_OPEN,
            'in progress' => Ticket::STATUS_IN_PROGRESS, 'under process' => Ticket::STATUS_IN_PROGRESS, 'processing' => Ticket::STATUS_IN_PROGRESS, 'in process' => Ticket::STATUS_IN_PROGRESS, 'ongoing' => Ticket::STATUS_IN_PROGRESS, 'in_progress' => Ticket::STATUS_IN_PROGRESS, 'progress' => Ticket::STATUS_IN_PROGRESS, 'wip' => Ticket::STATUS_IN_PROGRESS, 'working' => Ticket::STATUS_IN_PROGRESS, 'doing' => Ticket::STATUS_IN_PROGRESS, 'started' => Ticket::STATUS_IN_PROGRESS,
            'resolved' => Ticket::STATUS_RESOLVED, 'done' => Ticket::STATUS_RESOLVED, 'fixed' => Ticket::STATUS_RESOLVED, 'completed' => Ticket::STATUS_RESOLVED, 'complete' => Ticket::STATUS_RESOLVED,
            'closed' => Ticket::STATUS_CLOSED, 'close' => Ticket::STATUS_CLOSED, 'cancelled' => Ticket::STATUS_CLOSED, 'canceled' => Ticket::STATUS_CLOSED, 'archived' => Ticket::STATUS_CLOSED, 'rejected' => Ticket::STATUS_CLOSED,
        ];
    }

    private static function normalizeHeader(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($value)));
    }

    /**
     * Match each field to the first header whose name is one of its aliases.
     *
     * @param  array<int, string>  $headers  column index => header as typed
     * @return array<string, ?int> field => column index (null when not found)
     */
    public static function guessMappings(array $headers): array
    {
        $normalized = array_map(fn ($header): string => self::normalizeHeader((string) $header), $headers);
        $taken = [];
        $mappings = [];

        foreach (self::aliases() as $field => $aliases) {
            $mappings[$field] = null;
            foreach ($aliases as $alias) {
                $column = array_search($alias, $normalized, true);
                if ($column !== false && ! in_array($column, $taken, true)) {
                    $mappings[$field] = $column;
                    $taken[] = $column;
                    break;
                }
            }
        }

        return $mappings;
    }

    /**
     * Keep the upload, list its sheets and read the one most likely to hold the tickets.
     *
     * @return array<string, mixed> see prepare()
     */
    public static function load(UploadedFile $file, int $userId): array
    {
        $token = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
        $path = $file->storeAs('ticket-imports', "{$token}.{$extension}", 'local');

        return self::prepare($userId, $token, Storage::disk('local')->path($path), null, null);
    }

    /**
     * Re-read an upload from another sheet or another header row.
     *
     * @return array<string, mixed>|null null when the upload has expired
     */
    public static function switchSheet(int $userId, string $token, string $sheet, ?int $headerRow): ?array
    {
        $cached = Cache::get(self::cacheKey($userId, $token));
        if (! $cached || ! is_file($cached['path'])) {
            return null;
        }

        return self::prepare($userId, $token, $cached['path'], $sheet, $headerRow);
    }

    /**
     * Read one sheet, find its header row, and cache the data rows under the token.
     *
     * @return array{token: string, sheets: list<array{name: string, rows: int}>, sheet: string, header_row: int, headers: list<array{index: int, label: string, samples: list<string>}>, row_count: int, truncated: bool, mappings: array<string, ?int>}
     */
    private static function prepare(int $userId, string $token, string $path, ?string $sheet, ?int $headerRow): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $sheets = collect($reader->listWorksheetInfo($path))
            ->map(fn (array $info): array => ['name' => (string) $info['worksheetName'], 'rows' => (int) $info['totalRows']])
            ->values()
            ->all();

        if ($sheet === null || ! in_array($sheet, array_column($sheets, 'name'), true)) {
            $sheet = collect($sheets)->sortByDesc('rows')->first()['name'] ?? '';
        }

        $raw = self::readSheet($path, $sheet);
        $headerRow ??= self::detectHeaderRow($raw);
        $headerRow = max(1, min($headerRow, max(count($raw), 1)));

        $header = $raw[$headerRow - 1] ?? [];
        $rows = array_values(array_filter(array_slice($raw, $headerRow), fn (array $row): bool => collect($row)->filter(fn ($cell): bool => trim((string) $cell) !== '')->isNotEmpty()));
        $truncated = count($rows) > self::MAX_ROWS;
        $rows = array_slice($rows, 0, self::MAX_ROWS);

        $headers = [];
        foreach ($header as $index => $label) {
            $label = trim((string) $label);
            $samples = collect($rows)->pluck($index)->map(fn ($v): string => trim((string) $v))->filter()->take(3)->values()->all();
            if ($label === '' && $samples === []) {
                continue;
            }
            $headers[] = ['index' => $index, 'label' => $label !== '' ? $label : 'Column '.($index + 1), 'samples' => $samples];
        }

        Cache::put(self::cacheKey($userId, $token), ['path' => $path, 'rows' => $rows], now()->addMinutes(self::CACHE_TTL_MINUTES));

        return [
            'token' => $token,
            'sheets' => $sheets,
            'sheet' => $sheet,
            'header_row' => $headerRow,
            'headers' => $headers,
            'row_count' => count($rows),
            'truncated' => $truncated,
            'mappings' => self::guessMappings(array_column($headers, 'label', 'index')),
        ];
    }

    /**
     * Read the first rows of one sheet with formulas worked out, so a cell holding
     * =A2&" "&B2 imports as its text rather than the formula.
     *
     * @return list<array<int, mixed>>
     */
    private static function readSheet(string $path, string $sheet): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class(self::MAX_ROWS + self::HEADER_SCAN_ROWS + 1) implements IReadFilter
        {
            public function __construct(private int $maxRow) {}

            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row <= $this->maxRow;
            }
        });

        $spreadsheet = $reader->load($path);
        try {
            $worksheet = $spreadsheet->getSheetByName($sheet) ?? $spreadsheet->getSheet(0);
            try {
                $rows = $worksheet->toArray(null, true, false, false);
            } catch (\Throwable) {
                $rows = $worksheet->toArray(null, false, false, false);
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return array_values(array_map(fn ($row): array => array_values((array) $row), $rows));
    }

    /**
     * The header is the first row that is about as wide as the widest of the
     * opening rows — which steps over a title or a blank line above the table.
     *
     * @param  list<array<int, mixed>>  $raw
     */
    private static function detectHeaderRow(array $raw): int
    {
        $filled = array_map(
            fn (array $row): int => count(array_filter($row, fn ($cell): bool => trim((string) $cell) !== '')),
            array_slice($raw, 0, self::HEADER_SCAN_ROWS)
        );
        $widest = $filled ? max($filled) : 0;

        foreach ($filled as $i => $count) {
            if ($count > 0 && $count >= $widest * 0.6) {
                return $i + 1;
            }
        }

        return 1;
    }

    public static function cacheKey(int $userId, string $token): string
    {
        return "ticket-import:{$userId}:{$token}";
    }

    /**
     * @return list<array<int, mixed>>|null
     */
    public static function rows(int $userId, string $token): ?array
    {
        return Cache::get(self::cacheKey($userId, $token))['rows'] ?? null;
    }

    /** Drop the cached rows and the stored upload once they have been imported. */
    public static function forget(int $userId, string $token): void
    {
        $cached = Cache::pull(self::cacheKey($userId, $token));
        if (! empty($cached['path']) && is_file($cached['path'])) {
            @unlink($cached['path']);
        }
    }

    /**
     * Check every row against the mapping and options.
     *
     * @param  list<array<int, mixed>>  $rows
     * @param  array<string, ?int>  $mappings
     * @param  array{default_status?: string, duplicates?: string}  $options
     * @return list<array{line: int, state: string, issues: list<string>, data: array{title: string, description: string, status: string, group: ?string, created_at: ?string}}>
     */
    public static function review(array $rows, array $mappings, array $options = []): array
    {
        $defaultStatus = array_key_exists($options['default_status'] ?? '', Ticket::statuses()) ? $options['default_status'] : Ticket::STATUS_OPEN;
        $skipDuplicates = ($options['duplicates'] ?? 'skip') === 'skip';

        $existingTitles = Ticket::query()->pluck('title')->mapWithKeys(fn ($t): array => [mb_strtolower(trim($t)) => true])->all();
        $existingGroups = collect(Ticket::groupNames())->mapWithKeys(fn ($g): array => [mb_strtolower($g) => $g])->all();
        $seenTitles = [];
        $result = [];

        foreach ($rows as $i => $row) {
            $cell = fn (string $field): string => isset($mappings[$field]) && $mappings[$field] !== null && $mappings[$field] !== ''
                ? trim((string) ($row[(int) $mappings[$field]] ?? ''))
                : '';
            $issues = [];

            $title = preg_replace('/\s+/', ' ', $cell('title'));
            $description = $cell('description');
            $notes = [];
            if ($title === '') {
                $issues[] = 'Title is empty.';
            } elseif (mb_strlen($title) > 255) {
                if ($description === '') {
                    $description = $title;
                }
                $title = rtrim(mb_substr($title, 0, 254)).'…';
                $notes[] = 'Long title shortened; the full text is kept in the description.';
            }

            $statusText = $cell('status');
            $status = $statusText === '' ? $defaultStatus : (self::statusAliases()[strtolower($statusText)] ?? null);
            if ($status === null) {
                $issues[] = "Unknown status \"{$statusText}\".";
                $status = $defaultStatus;
            }

            $group = preg_replace('/\s+/', ' ', $cell('group'));
            if (mb_strlen($group) > 100) {
                $issues[] = 'Group is longer than 100 characters.';
            }
            $group = $group === '' ? null : ($existingGroups[mb_strtolower($group)] ??= $group);

            $createdAt = null;
            if ($cell('created_at') !== '') {
                $createdAt = self::parseDate($cell('created_at'));
                if ($createdAt === null) {
                    $issues[] = "Created date \"{$cell('created_at')}\" is not a date.";
                }
            }

            $state = $issues ? 'error' : 'ready';
            $titleKey = mb_strtolower($title);
            if ($state === 'ready' && $skipDuplicates && (isset($existingTitles[$titleKey]) || isset($seenTitles[$titleKey]))) {
                $state = 'skip';
                $issues[] = isset($existingTitles[$titleKey]) ? 'A ticket with this title already exists.' : 'Same title as an earlier row in this file.';
            }
            if ($title !== '') {
                $seenTitles[$titleKey] = true;
            }

            $result[] = [
                'line' => $i + 2,
                'state' => $state,
                'issues' => [...$issues, ...$notes],
                'data' => [
                    'title' => $title,
                    'description' => $description,
                    'status' => $status,
                    'group' => $group,
                    'created_at' => $createdAt,
                ],
            ];
        }

        return $result;
    }

    private static function parseDate(string $value): ?string
    {
        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateTimeString();
            }

            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<array{state: string}>  $reviewed
     * @return array{total: int, ready: int, skip: int, error: int}
     */
    public static function summary(array $reviewed): array
    {
        $counts = array_count_values(array_column($reviewed, 'state'));

        return [
            'total' => count($reviewed),
            'ready' => $counts['ready'] ?? 0,
            'skip' => $counts['skip'] ?? 0,
            'error' => $counts['error'] ?? 0,
        ];
    }
}
