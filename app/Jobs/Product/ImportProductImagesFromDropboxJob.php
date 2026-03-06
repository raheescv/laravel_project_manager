<?php

namespace App\Jobs\Product;

use App\Models\Tenant;
use App\Services\ProductImageFolderMatcher;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ImportProductImagesFromDropboxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $sharedUrl,
        protected ?int $tenantId = null,
    ) {}

    public function handle(ProductImageFolderMatcher $matcher): void
    {
        $tenantService = app(TenantService::class);

        if ($this->tenantId) {
            $tenant = Tenant::query()->find($this->tenantId);

            if ($tenant) {
                $tenantService->setCurrentTenant($tenant);
            }
        }

        try {
            $matcher->importMatchedImagesFromDropboxFolder($this->sharedUrl);
        } finally {
            $tenantService->clearCurrentTenant();
        }
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
