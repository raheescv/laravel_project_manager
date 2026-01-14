<?php

namespace App\Console\Commands\SingleUse;

use App\Models\Branch;
use App\Models\Country;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\UniqueNoCounter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateLatestUniqueNoForTableCommand extends Command
{
    protected $signature = 'app:generate-latest-unique-no-for-table {--segment=Sale : Unique number segment}';

    protected $description = 'Generate latest unique number for the unique_no_counters table from existing data (invoices, barcodes, etc.)';

    public function handle()
    {
        $segment = $this->option('segment');

        if ($segment === 'Barcode') {
            return $this->handleBarcodeBackfill();
        }

        $this->info("Collecting invoice counters for segment: {$segment}");

        $counters = [];
        $branches = Branch::pluck('id', 'id');
        foreach ($branches as $branchId) {
            $sale = Sale::withTrashed()->where('branch_id', $branchId)->latest('id')->first();
            if (! $sale) {
                continue;
            }
            $parsed = $this->parseInvoiceNumber($sale->date, $sale->invoice_no, $sale->branch->code);
            if (! $parsed) {
                $this->warn("Skipping malformed invoice number: {$sale->invoice_no}");

                continue;
            }
            $counters[] = [
                'tenant_id' => $sale->tenant_id ?? null,
                'year' => $parsed['year'],
                'branch_code' => $parsed['branch_code'],
                'segment' => $segment,
                'number' => $parsed['number'],
            ];
        }
        if (empty($counters)) {
            $this->warn('No invoice numbers were found to process.');

            return self::SUCCESS;
        }
        DB::transaction(function () use ($counters): void {
            foreach ($counters as $data) {
                if (! $data['tenant_id']) {
                    continue;
                }
                UniqueNoCounter::firstOrCreate([
                    'tenant_id' => $data['tenant_id'],
                    'year' => $data['year'],
                    'branch_code' => $data['branch_code'],
                    'segment' => $data['segment'],
                ], [
                    'number' => $data['number'],
                ]);
            }
        });

        return self::SUCCESS;
    }

    private function handleBarcodeBackfill()
    {
        $this->info('Collecting barcode counters from Products and ProductUnits...');

        $counters = [];
        $tenants = Tenant::all();
        $countryId = cache('country_id', Country::QATAR);
        $branchCode = 'M'; // Default branch code

        // Determine year format based on country
        if ($countryId == Country::INDIA) {
            $year = now()->format('y').'/'.now()->addYear()->format('y');
            if (now()->lt(now()->copy()->month(3)->day(31))) {
                $year = now()->subYear()->format('y').'/'.now()->format('y');
            }
        } else {
            $year = now()->format('y');
        }

        foreach ($tenants as $tenant) {
            // Get max barcode from Products
            $maxProductBarcode = Product::withTenant($tenant->id)
                ->whereNotNull('barcode')
                ->where('barcode', '!=', '')
                ->max('barcode');

            // Get max barcode from ProductUnits
            $maxProductUnitBarcode = ProductUnit::withTenant($tenant->id)
                ->whereNotNull('barcode')
                ->where('barcode', '!=', '')
                ->max('barcode');

            // Extract numeric part from both
            $maxNumeric = 0;

            if ($maxProductBarcode) {
                $numeric = $this->extractNumericFromBarcode($maxProductBarcode);
                $maxNumeric = max($maxNumeric, $numeric);
            }

            if ($maxProductUnitBarcode) {
                $numeric = $this->extractNumericFromBarcode($maxProductUnitBarcode);
                $maxNumeric = max($maxNumeric, $numeric);
            }

            // Ensure minimum of 8000
            $maxNumeric = max(8000, $maxNumeric);

            if ($maxNumeric > 0) {
                $counters[] = [
                    'tenant_id' => $tenant->id,
                    'year' => 1,
                    'branch_code' => $branchCode,
                    'segment' => 'Barcode',
                    'number' => $maxNumeric,
                ];
                $this->info("Tenant {$tenant->id} ({$tenant->name}): Max barcode number = {$maxNumeric}");
            }
        }

        if (empty($counters)) {
            $this->warn('No barcodes were found to process.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($counters): void {
            foreach ($counters as $data) {
                UniqueNoCounter::firstOrCreate([
                    'tenant_id' => $data['tenant_id'],
                    'year' => $data['year'],
                    'branch_code' => $data['branch_code'],
                    'segment' => $data['segment'],
                ], [
                    'number' => $data['number'],
                ]);
            }
        });

        $this->info('Successfully backfilled barcode counters.');

        return self::SUCCESS;
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

    private function parseInvoiceNumber(string $date, string $invoice_no, ?string $branchCode): ?array
    {
        if (! $date || ! $branchCode) {
            return null;
        }
        $year = date('y', strtotime($date));
        $exploded_invoice = explode('-', $invoice_no);
        $number = $exploded_invoice[3];

        return [
            'branch_code' => $branchCode,
            'year' => $year,
            'number' => (int) $number,
        ];
    }
}
