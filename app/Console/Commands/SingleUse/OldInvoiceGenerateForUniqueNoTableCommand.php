<?php

namespace App\Console\Commands\SingleUse;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\UniqueNoCounter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OldInvoiceGenerateForUniqueNoTableCommand extends Command
{
    protected $signature = 'app:old-invoice-generate-for-unique-no-table-command {--segment=Sale : Unique number segment}';

    protected $description = 'Backfill the unique_no_counters table using historic invoice numbers.';

    public function handle()
    {
        $segment = $this->option('segment');

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
                UniqueNoCounter::firstOrCreate([
                    'year' => $data['year'],
                    'branch_code' => $data['branch_code'],
                    'segment' => $data['segment'],
                    'number' => $data['number'],
                ]);
            }
        });

        return self::SUCCESS;
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
