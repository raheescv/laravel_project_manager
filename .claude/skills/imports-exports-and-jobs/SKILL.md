---
name: imports-exports-and-jobs
description: "Use when adding or changing an Excel/CSV export or import, or any queued job — anything under app/Exports/, app/Imports/, app/Jobs/, or a Livewire 'Export'/'Import' button. Covers the FromQuery + filters export shape, queued export jobs with the ExportCompleted notification, chunked imports with progress events, the tenant-context requirement inside jobs, and the retry/timeout settings that prevent duplicate imports. Read before writing a job that touches tenant data or an import that can partially fail."
---

# Imports, Exports, and Queued Jobs

Roughly 60 export classes, 13 import classes, and 38 jobs. All three share one non-obvious hazard: **queued code runs outside the web request, so tenant and branch context must be passed in and re-established explicitly.**

## Exports

Export classes take a `$filters` array matching the Livewire table's filter properties, so the spreadsheet and the screen always agree:

```php
class SaleExport implements FromQuery, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public array $filters = []) {}

    public function query()
    {
        return Sale::query()
            ->with(['branch:id,name', 'account:id,name', 'createdUser:id,name'])
            ->when($this->filters['branch_id'] ?? null, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($this->filters['from_date'] ?? null, fn ($q, $v) => $q->whereDate('date', '>=', $v));
    }
}
```

- `FromQuery` (not `FromCollection`) so large exports stream.
- Constrain eager loads to the columns used (`'branch:id,name'`).
- `WithHeadings` + `WithMapping` for column order; `WithColumnFormatting` for dates and money; `WithEvents`/`AfterSheet` for widths and styling.
- Starting from the **model** keeps `TenantScope` and `AssignedBranchScope` applied. A `DB::table()` export leaks across tenants.

Large exports are queued and notify when ready:

```php
class ExportSaleJob implements ShouldQueue
{
    public function handle()
    {
        $exportFileName = 'exports/Sale_'.now()->timestamp.'.xlsx';
        Excel::store(new SaleExport(), $exportFileName, 'public');
        $this->user->notify(new ExportCompleted('Sale', $exportFileName));
    }
}
```

`ExportCompleted` goes to both the database channel (the in-app notification bell) and mail, with a download link. Reuse it — don't write a new notification per module.

## Imports

Imports are chunked and driven from a queued job. `app/Imports/ProductImport.php` is the fullest reference:

```php
class ProductImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow
```

- **`ToCollection`, not `ToModel`** — rows are pushed through the existing domain actions (`Product\UpdateAction`, `Inventory\UpdateAction`) so imported data gets the same validation and side effects as UI-entered data.
- **Preload lookups into keyed arrays** (`existingById`, `existingByName`, `inventoryByProductId`) before the loop. Querying per row turns a 5,000-row import into 15,000 queries.
- **Column mappings are passed in** (`$mappings`), not hardcoded, because tenants upload differently-shaped sheets.
- **A duplicate strategy is explicit** (`skip` / update). Decide it at the call site.
- **Collect per-row errors** instead of aborting; report them at the end. A single bad row must not discard 4,999 good ones.
- Progress is broadcast with `FileImportProgress` / `FileImportCompleted` events; `RowCountImport` and `LimitedRowImport` pre-count and preview the file.

## Jobs: the two things that bite

**1. Tenant context must be re-established.** The job runs with no session and no authenticated user, so `TenantService` resolves to nothing and every `BelongsToTenant` query silently returns rows for the wrong tenant — or none:

```php
public function __construct(protected $user_id, protected $filePath, protected $branchId = null, protected $tenantId = null, /* … */) {}

public function handle()
{
    // Set tenant context so BelongsToTenant / TenantScope resolve
    $tenantService = app(TenantService::class);
    if ($this->tenantId) {
        $tenant = Tenant::find($this->tenantId);
        if ($tenant) {
            $tenantService->setCurrentTenant($tenant);
        }
    }

    try {
        $this->runImport();
    } finally {
        $tenantService->clearCurrentTenant();   // always release it
    }
}
```

Always pass `tenant_id`, `branch_id`, and the acting `user_id` into the constructor. Never rely on `session('branch_id')` or `Auth::id()` inside a job. Clear the tenant in a `finally` — a worker process is long-lived and would otherwise carry that tenant into the next job it picks up.

**2. Retries duplicate work.** Imports and any non-idempotent job set:

```php
public $tries = 1;      // a retry could re-import a partially-processed file and create duplicates
public $timeout = 3600; // keep below the queue retry_after (DB_QUEUE_RETRY_AFTER) or a running job is re-picked
```

If `timeout` exceeds `retry_after`, the queue hands the same job to a second worker while the first is still running — the classic double-import. Check both numbers together.

Uploaded files land on `Storage` and the path is passed to the job; the job cleans up after itself. Queue work runs under Horizon.

## Adding one

1. Export → `app/Exports/<Model>Export.php` with a `$filters` constructor; wire the Livewire table's existing filter array into it.
2. Queue it via `app/Jobs/Export/Export<Model>Job.php` + `ExportCompleted` if the dataset can be large.
3. Import → `app/Imports/<Model>Import.php` delegating to the module's actions, plus `app/Jobs/<Module>/Import<Model>Job.php` with `tries = 1`, an explicit timeout, and tenant context.
4. Gate the button with the module's `import`/`export` permission.
