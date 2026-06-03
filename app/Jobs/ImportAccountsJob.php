<?php

namespace App\Jobs;

use App\Events\FileImportProgress;
use App\Imports\AccountImport;
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

class ImportAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $userId,
        protected string $filePath,
        protected ?int $tenantId = null,
        protected array $mappings = [],
        protected string $duplicateStrategy = 'skip'
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
        $file = $this->resolveFilePath();

        if (! $file || ! file_exists($file)) {
            throw new Exception("File [{$this->filePath}] does not exist and can therefore not be imported.");
        }

        $collection = Excel::toCollection(new \stdClass(), $file)->first();
        $totalRows = $collection->filter(function ($row) {
            return $row->filter()->isNotEmpty();
        })->count();
        $totalRows = max($totalRows - 1, 1);

        Excel::import(new AccountImport(
            $this->userId,
            $totalRows,
            $this->tenantId,
            $this->mappings,
            $this->duplicateStrategy
        ), $file);

        event(new FileImportProgress($this->userId, 'Account', 100));

        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function resolveFilePath(): ?string
    {
        if (Storage::disk('public')->exists($this->filePath)) {
            return Storage::disk('public')->path($this->filePath);
        }

        if (Storage::exists($this->filePath)) {
            return Storage::path($this->filePath);
        }

        foreach ([
            storage_path('app/public/'.$this->filePath),
            storage_path('app/'.$this->filePath),
        ] as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

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

        Notification::send($user, new ImportErrorsNotification('Account', '', $errors));

        event(new FileImportProgress($this->userId, 'Account', -1, $exception->getMessage()));
    }
}
