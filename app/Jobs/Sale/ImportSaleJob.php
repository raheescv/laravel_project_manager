<?php

namespace App\Jobs\Sale;

use App\Imports\SaleImport;
use App\Jobs\Middleware\RunInTenant;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ImportSaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected $user_id, protected $filePath, protected $branchId = null, protected ?int $tenantId = null)
    {
        $this->tenantId ??= app(TenantService::class)->getCurrentTenantId();
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RunInTenant($this->tenantId)];
    }

    public function handle()
    {
        $file = storage_path('app/public/'.$this->filePath);
        $collection = Excel::toCollection(new \stdClass(), $file)->first();
        $totalRows = $collection->filter(function ($row) {
            return $row->filter()->isNotEmpty();
        })->count();

        $totalRows--;
        Excel::import(new SaleImport($this->user_id, $totalRows, $this->branchId), $file);
        unlink($file);
    }
}
