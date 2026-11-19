<?php

namespace App\Jobs\Category;

use App\Imports\CategoryImport;
use App\Jobs\Middleware\RunInTenant;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ImportCategoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected $user_id, protected $filePath, protected ?int $tenantId = null)
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
        $totalRows = Excel::toCollection(new \stdClass(), $file)->first()->count() - 1;
        Excel::import(new CategoryImport($this->user_id, $totalRows), $file);
        unlink($file);
    }
}
