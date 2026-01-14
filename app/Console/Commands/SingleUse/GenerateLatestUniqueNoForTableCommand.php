<?php

namespace App\Console\Commands\SingleUse;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\UniqueNoCounter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateLatestUniqueNoForTableCommand extends Command
{
    private const SEGMENT_BARCODE = 'Barcode';

    private const SEGMENT_SALE = 'Sale';

    private const DEFAULT_BRANCH_CODE = 'M';

    private const MIN_BARCODE_NUMBER = 8000;

    private const INVOICE_NUMBER_INDEX = 3;

    protected $signature = 'app:generate-latest-unique-no-for-table {--segment=All : Unique number segment}';

    protected $description = 'Generate latest unique number for the unique_no_counters table from existing data (invoices, barcode, etc.)';

    public function handle(): int
    {
        $segment = $this->option('segment');

        if ($segment == 'All' || $segment === self::SEGMENT_BARCODE) {
            $this->handleBarcodeBackfill();
        }

        if ($segment == 'All' || $segment === self::SEGMENT_SALE) {
            $this->handleSaleBackfill();
        }

        return self::SUCCESS;
    }

    private function handleBarcodeBackfill(): void
    {
        $this->info('Collecting barcode counters from Products and ProductUnits...');

        $counters = $this->collectBarcodeCounters();

        if (empty($counters)) {
            $this->warn('No barcode were found to process.');

            return;
        }

        $this->saveCounters($counters);
        $this->info('Successfully backfilled barcode counters.');
    }

    private function collectBarcodeCounters(): array
    {
        $counters = [];
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $maxNumeric = $this->getMaxBarcodeForTenant($tenant->id);

            if ($maxNumeric > 0) {
                $counters[] = [
                    'tenant_id' => $tenant->id,
                    'year' => 1,
                    'branch_code' => self::DEFAULT_BRANCH_CODE,
                    'segment' => self::SEGMENT_BARCODE,
                    'number' => $maxNumeric,
                ];
                $this->info("Tenant {$tenant->id} ({$tenant->name}): Max barcode number = {$maxNumeric}");
            }
        }

        return $counters;
    }

    private function getMaxBarcodeForTenant(int $tenantId): int
    {
        $maxProductBarcode = $this->getMaxBarcodeFromProducts($tenantId);
        $maxProductUnitBarcode = $this->getMaxBarcodeFromProductUnits($tenantId);

        $maxNumeric = max(
            $this->extractNumericFromBarcode($maxProductBarcode),
            $this->extractNumericFromBarcode($maxProductUnitBarcode)
        );

        return max(self::MIN_BARCODE_NUMBER, $maxNumeric);
    }

    private function getMaxBarcodeFromProducts(int $tenantId): ?string
    {
        return Product::withTenant($tenantId)
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->max('barcode');
    }

    private function getMaxBarcodeFromProductUnits(int $tenantId): ?string
    {
        return ProductUnit::withTenant($tenantId)
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->max('barcode');
    }

    private function handleSaleBackfill(): void
    {
        $this->info('Collecting invoice counters for segment: Sale');

        $counters = $this->collectSaleCounters();

        if (empty($counters)) {
            $this->warn('No invoice numbers were found to process.');

            return;
        }

        $this->saveCounters($counters);
    }

    private function collectSaleCounters(): array
    {
        $counters = [];
        $branches = Branch::pluck('id', 'id');

        foreach ($branches as $branchId) {
            $sale = $this->getLatestSaleForBranch($branchId);

            if (! $sale) {
                continue;
            }

            $parsed = $this->parseInvoiceNumber($sale->date, $sale->invoice_no, $sale->branch->code);

            if (! $parsed) {
                $this->warn("Skipping malformed invoice number: {$sale->invoice_no}");

                continue;
            }

            if (! $sale->tenant_id) {
                continue;
            }

            $counters[] = [
                'tenant_id' => $sale->tenant_id,
                'year' => $parsed['year'],
                'branch_code' => $parsed['branch_code'],
                'segment' => 'Sale',
                'number' => $parsed['number'],
            ];
        }

        return $counters;
    }

    private function getLatestSaleForBranch(int $branchId): ?Sale
    {
        return Sale::withTrashed()
            ->where('branch_id', $branchId)
            ->latest('id')
            ->first();
    }

    private function saveCounters(array $counters): void
    {
        DB::transaction(function () use ($counters): void {
            foreach ($counters as $data) {
                if (empty($data['tenant_id'])) {
                    continue;
                }

                UniqueNoCounter::firstOrCreate(
                    [
                        'tenant_id' => $data['tenant_id'],
                        'year' => $data['year'],
                        'branch_code' => $data['branch_code'],
                        'segment' => $data['segment'],
                    ],
                    [
                        'number' => $data['number'],
                    ]
                );
            }
        });
    }

    private function extractNumericFromBarcode(?string $barcode): int
    {
        if (! $barcode) {
            return 0;
        }

        // If barcode is purely numeric, return it
        if (is_numeric($barcode)) {
            return (int) $barcode;
        }

        // Extract numeric part from prefixed barcode (e.g., "TFQ01" -> 1, "ABC123" -> 123)
        if (preg_match('/(\d+)$/', $barcode, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function parseInvoiceNumber(string $date, string $invoiceNo, ?string $branchCode): ?array
    {
        if (! $date || ! $branchCode) {
            return null;
        }

        $year = date('y', strtotime($date));
        $explodedInvoice = explode('-', $invoiceNo);

        if (! isset($explodedInvoice[self::INVOICE_NUMBER_INDEX])) {
            return null;
        }

        return [
            'branch_code' => $branchCode,
            'year' => $year,
            'number' => (int) $explodedInvoice[self::INVOICE_NUMBER_INDEX],
        ];
    }
}
