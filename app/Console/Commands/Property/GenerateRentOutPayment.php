<?php

namespace App\Console\Commands\Property;

use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateRentOutPayment extends Command
{
    protected $signature = 'property:generate-rent-payment';

    protected $description = 'Generates payment terms for active rent outs based on their start_date, rent, and no_of_terms';

    public function handle()
    {
        $this->info('Starting rent payment term generation...');

        $rentOuts = RentOut::withoutGlobalScopes()
            ->where('status', RentOutStatus::Occupied)
            ->whereNotNull('no_of_terms')
            ->where('no_of_terms', '>', 0)
            ->get();

        if ($rentOuts->isEmpty()) {
            $this->info('No active rent outs found requiring payment generation.');

            return 0;
        }

        $this->info("Found {$rentOuts->count()} active rent out(s) to process.");

        $generatedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($rentOuts as $rentOut) {
            try {
                DB::beginTransaction();

                $existingTerms = $rentOut->paymentTerms()->count();

                if ($existingTerms >= $rentOut->no_of_terms) {
                    $skippedCount++;
                    DB::rollBack();

                    continue;
                }

                $startDate = Carbon::parse($rentOut->start_date);
                $rentAmount = $rentOut->rent;
                $discount = $rentOut->discount ? ($rentOut->discount / $rentOut->no_of_terms) : 0;

                for ($i = $existingTerms; $i < $rentOut->no_of_terms; $i++) {
                    $dueDate = $startDate->copy()->addMonths($i);
                    if ($rentOut->collection_starting_day) {
                        $dueDate->day = min($rentOut->collection_starting_day, $dueDate->daysInMonth);
                    }

                    RentOutPaymentTerm::create([
                        'tenant_id' => $rentOut->tenant_id,
                        'branch_id' => $rentOut->branch_id,
                        'rent_out_id' => $rentOut->id,
                        'amount' => $rentAmount,
                        'discount' => round($discount, 2),
                        'total' => round($rentAmount - $discount, 2),
                        'due_date' => $dueDate,
                        'status' => 'pending',
                        'created_by' => 1,
                    ]);
                }

                DB::commit();
                $generatedCount++;
                $this->info("Generated payment terms for Rent Out ID {$rentOut->id}");
            } catch (\Exception $e) {
                DB::rollBack();
                $failedCount++;
                $this->error("Failed for Rent Out ID {$rentOut->id}: {$e->getMessage()}");
            }
        }

        $this->info("\nSummary:");
        $this->info("  Generated: {$generatedCount}");
        $this->info("  Skipped (already complete): {$skippedCount}");
        $this->info("  Failed: {$failedCount}");

        return $failedCount > 0 ? 1 : 0;
    }
}
