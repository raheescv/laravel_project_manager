<?php

namespace App\Jobs;

use App\Events\FileImportProgress;
use App\Imports\GeneralVoucherImport;
use App\Imports\QuickBooksVoucherImport;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ImportErrorsNotification;
use App\Services\TenantService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportGeneralVoucherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $userId,
        protected string $filePath,
        protected int $branchId,
        protected ?int $tenantId = null,
        protected array $mappings = [],
        protected string $importFormat = 'normal'
    ) {}

    public function handle(): void
    {
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
            throw new Exception("File [{$this->filePath}] does not exist and can therefore not be imported.");
        }

        if ($this->importFormat === 'quickbooks') {
            // For QuickBooks, read from Sheet1 (index 1) and count non-empty rows
            $allSheets = Excel::toCollection(new \stdClass(), $file);
            $sheetData = $allSheets[1] ?? $allSheets[0] ?? collect();
            $totalRows = $sheetData->filter(fn ($row) => $row->filter()->isNotEmpty())->count();

            Excel::import(new QuickBooksVoucherImport($this->userId, $totalRows, $this->branchId, $this->mappings), $file);
        } else {
            $collection = Excel::toCollection(new \stdClass(), $file)->first();
            $totalRows = $collection->filter(function ($row) {
                return $row->filter()->isNotEmpty();
            })->count();

            $totalRows--;
            Excel::import(new GeneralVoucherImport($this->userId, $totalRows, $this->branchId, $this->mappings), $file);
        }

        // Send 100% progress to broadcast completion
        event(new FileImportProgress($this->userId, 'GeneralVoucher', 100));

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Handle a job failure - notify the user with the error details.
     */
    public function failed(\Throwable $exception): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $errors = [
            [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
        ];

        Notification::send($user, new ImportErrorsNotification('General Voucher', '', $errors));

        // Broadcast failure so the frontend progress bar shows the error
        event(new FileImportProgress($this->userId, 'GeneralVoucher', -1, $exception->getMessage()));
    }
}
