<?php

namespace App\Console\Commands\SingleUse;

use App\Actions\Product\ProductUnit\CreateAction;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CreateProductUnitsCommand extends Command
{
    protected $signature = 'product-units:create {--tenant-id= : Specific tenant ID to process} {--skip-existing : Skip products that already have ProductUnits}';

    protected $description = 'Create ProductUnits for all Products using the CreateAction';

    public function handle()
    {
        $this->info('Starting ProductUnit creation for all Products...');

        $tenantId = $this->option('tenant-id');
        $skipExisting = $this->option('skip-existing');

        // Build query - use withoutTenant to get all products across tenants
        $query = Product::withoutTenant()->where('type', 'product');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
            $this->info("Processing products for tenant ID: {$tenantId}");
        } else {
            $this->info('Processing products for all tenants');
        }

        $totalProducts = $query->count();
        $this->info("Total products to process: {$totalProducts}");

        if ($totalProducts === 0) {
            $this->warn('No products found to process.');

            return Command::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($totalProducts);
        $progressBar->start();

        $created = 0;
        $skipped = 0;
        $errors = 0;
        $tenantService = app(TenantService::class);

        $query->chunk(100, function ($products) use (&$created, &$skipped, &$errors, $skipExisting, $progressBar, $tenantService) {
            foreach ($products as $product) {
                $progressBar->advance();

                try {
                    $units = Unit::whereIn('name', ['30yard', '58taka'])->get();
                    foreach ($units as $unit) {
                        // Check if ProductUnit already exists for this product and unit (without tenant scope)
                        $existingProductUnit = ProductUnit::withoutTenant()->where('product_id', $product->id)->where('sub_unit_id', $unit->id)->first();

                        if ($existingProductUnit) {
                            if ($skipExisting) {
                                $skipped++;

                                continue;
                            }
                        }
                        // Set tenant context for this product
                        $tenant = Tenant::find($product->tenant_id);
                        if ($tenant) {
                            $tenantService->setCurrentTenant($tenant);
                        }
                        // Prepare data for CreateAction
                        $data = [
                            'product_id' => $product->id,
                            'sub_unit_id' => $unit->id,
                            'conversion_factor' => $unit->code == '30yard' ? '27.5' : '23', // Base unit has conversion factor of 1
                            'barcode' => null,
                            'tenant_id' => $product->tenant_id,
                        ];

                        Session::put('tenant_id', $product->tenant_id);
                        $createAction = new CreateAction();
                        $response = $createAction->execute($data);

                        if ($response['success']) {
                            $created++;
                        } else {
                            $errors++;
                            $this->newLine();
                            $this->error("Failed to create ProductUnit for Product ID {$product->id}: {$response['message']}");
                            Log::error("Failed to create ProductUnit for Product ID {$product->id}", [
                                'product_id' => $product->id,
                                'error' => $response['message'],
                                'data' => $data,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Exception creating ProductUnit for Product ID {$product->id}: {$e->getMessage()}");
                    Log::error("Exception creating ProductUnit for Product ID {$product->id}", [
                        'product_id' => $product->id,
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        });

        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        // Summary
        $this->info('ProductUnit creation completed!');
        $this->info("Created: {$created}");
        $this->info("Skipped: {$skipped}");
        $this->info("Errors: {$errors}");

        return Command::SUCCESS;
    }
}
