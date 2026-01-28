<?php

namespace App\Jobs\Product;

use App\Imports\ProductImport;
use App\Models\Tenant;
use App\Services\TenantService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected $user_id, protected $filePath, protected $branchId = null, protected $tenantId = null, protected $mappings = []) {}

    public function handle()
    {
        // Set tenant context so BelongsToTenant, TenantScope
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
        // Try to resolve the file path using Storage facade
        $file = null;

        // First try the public disk (where files are stored)
        if (Storage::disk('public')->exists($this->filePath)) {
            $file = Storage::disk('public')->path($this->filePath);
        } elseif (Storage::exists($this->filePath)) {
            // Fallback to default disk
            $file = Storage::path($this->filePath);
        } else {
            // Fallback: try different possible locations
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

        $collection = Excel::toCollection(new \stdClass(), $file)->first();
        $totalRows = $collection->filter(function ($row) {
            return $row->filter()->isNotEmpty();
        })->count();

        $totalRows--;
        Excel::import(new ProductImport($this->user_id, $totalRows, $this->branchId, $this->mappings), $file);

        // Clean up the file after import
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
