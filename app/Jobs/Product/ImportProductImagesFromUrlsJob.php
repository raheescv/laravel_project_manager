<?php

namespace App\Jobs\Product;

use App\Models\Product;
use App\Models\Tenant;
use App\Services\ProductImageUrlImporter;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ImportProductImagesFromUrlsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 600;

    /**
     * @param  array<int, string>  $urls
     */
    public function __construct(
        protected int $productId,
        protected array $urls,
        protected ?int $tenantId = null,
    ) {}

    public function handle(ProductImageUrlImporter $importer): void
    {
        $tenantService = app(TenantService::class);

        if ($this->tenantId) {
            $tenant = Tenant::query()->find($this->tenantId);

            if ($tenant) {
                $tenantService->setCurrentTenant($tenant);
            }
        }

        try {
            $product = Product::query()->find($this->productId);

            if ($product) {
                $importer->import($product, $this->urls);
            }
        } finally {
            $tenantService->clearCurrentTenant();
        }
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
