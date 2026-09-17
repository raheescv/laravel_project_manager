<?php

namespace App\Jobs\Student;

use App\Events\FileImportProgress;
use App\Imports\RowCountImport;
use App\Imports\StudentImport;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ImportErrorsNotification;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Imports an uploaded student sheet in the background.
 *
 * The file sits on the private `local` disk (it holds QIDs and parents' mobiles)
 * and is deleted when the job ends. Progress is broadcast as FileImportProgress
 * and also kept under statusKey($runId), which the import screen polls — so the
 * screen works whether or not broadcasting is configured.
 */
class ImportStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** A retry would re-run rows that already imported. */
    public $tries = 1;

    public $timeout = 1800;

    /**
     * @param  array<string, string>  $mappings  field => heading key; empty for the template's own headings
     */
    public function __construct(
        protected int $userId,
        protected string $filePath,
        protected int $tenantId,
        protected string $duplicateStrategy = 'skip',
        protected array $mappings = [],
        protected ?string $runId = null,
    ) {}

    public static function statusKey(string $runId): string
    {
        return "student-import:run:{$runId}";
    }

    public function handle(): void
    {
        $tenantService = app(TenantService::class);
        $tenantService->setCurrentTenant(Tenant::findOrFail($this->tenantId));

        try {
            $file = Storage::disk('local')->path($this->filePath);

            $counter = new RowCountImport();
            Excel::import($counter, $file);

            $import = new StudentImport($this->userId, max(1, $counter->getCount()), $this->duplicateStrategy, $this->mappings, fn (int $progress) => $this->remember(['progress' => $progress]));
            Excel::import($import, $file);

            $failed = count($import->errors);
            $summary = "{$import->created} added, {$import->updated} updated, {$import->skipped} skipped".($failed ? ", {$failed} not imported" : '');
            event(new FileImportProgress($this->userId, 'Student', 100, $summary));
            $this->remember([
                'progress' => 100,
                'message' => $summary,
                'created' => $import->created,
                'updated' => $import->updated,
                'skipped' => $import->skipped,
                'failed' => $failed,
                'errors' => array_slice($import->errors, 0, 50),
            ]);

            if ($import->errors && ($user = User::find($this->userId))) {
                Notification::send($user, new ImportErrorsNotification('Student', '', $import->errors));
            }
        } finally {
            Storage::disk('local')->delete($this->filePath);
            $tenantService->clearCurrentTenant();
        }
    }

    public function failed(\Throwable $exception): void
    {
        event(new FileImportProgress($this->userId, 'Student', -1, $exception->getMessage()));
        $this->remember(['progress' => -1, 'message' => $exception->getMessage()]);
    }

    private function remember(array $status): void
    {
        if ($this->runId) {
            Cache::put(self::statusKey($this->runId), $status, now()->addHours(6));
        }
    }
}
