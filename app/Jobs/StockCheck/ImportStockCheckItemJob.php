<?php

namespace App\Jobs\StockCheck;

use App\Events\FileImportCompleted;
use App\Events\FileImportProgress;
use App\Imports\StockCheckItemImport;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportStockCheckItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $userId,
        protected string $filePath,
        protected int $stockCheckId,
        protected ?int $tenantId = null,
    ) {}

    public function handle(): void
    {
        $tenantService = app(TenantService::class);
        if ($this->tenantId) {
            /** @var Tenant|null $tenant */
            $tenant = Tenant::find($this->tenantId);
            if ($tenant) {
                $tenantService->setCurrentTenant($tenant);
            }
        }

        try {
            $this->runImport();
        } finally {
            $tenantService->clearCurrentTenant();
        }
    }

    private function runImport(): void
    {
        $file = null;

        if (Storage::disk('public')->exists($this->filePath)) {
            $file = Storage::disk('public')->path($this->filePath);
        } elseif (Storage::exists($this->filePath)) {
            $file = Storage::path($this->filePath);
        } else {
            $possiblePaths = [
                storage_path('app/public/'.$this->filePath),
                storage_path('app/'.$this->filePath),
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $file = $path;
                    break;
                }
            }
        }

        if (! $file || ! file_exists($file)) {
            event(new FileImportCompleted($this->userId, 'StockCheck', [
                ['message' => "File [{$this->filePath}] does not exist and can therefore not be imported."],
            ]));

            return;
        }

        // Count total rows
        $collection = Excel::toCollection(new \stdClass(), $file)->first();
        $totalRows = $collection->filter(fn ($row) => $row->filter()->isNotEmpty())->count();

        event(new FileImportProgress($this->userId, 'StockCheck', 10, 'Starting import...'));

        $import = new StockCheckItemImport($this->stockCheckId, $this->userId, $totalRows);
        Excel::import($import, $file);

        $errors = $import->getErrors();
        $updatedCount = $import->getUpdatedCount();

        if (! empty($errors)) {
            event(new FileImportCompleted($this->userId, 'StockCheck', $errors));
        }

        event(new FileImportProgress(
            $this->userId,
            'StockCheck',
            100,
            "{$updatedCount} items updated successfully.".(! empty($errors) ? ' '.count($errors).' errors.' : '')
        ));

        // Clean up file
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
