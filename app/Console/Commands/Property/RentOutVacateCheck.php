<?php

namespace App\Console\Commands\Property;

use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RentOutVacateCheck extends Command
{
    protected $signature = 'property:rent-out-vacate-check';

    protected $description = 'Checks vacate dates and updates statuses';

    public function handle()
    {
        $this->info('Checking for rent outs with passed vacate dates...');

        $vacatedRentOuts = RentOut::withoutGlobalScopes()
            ->where('status', RentOutStatus::Occupied)
            ->whereNotNull('vacate_date')
            ->where('vacate_date', '<=', Carbon::today())
            ->with(['customer', 'property'])
            ->get();

        if ($vacatedRentOuts->isEmpty()) {
            $this->info('No rent outs with passed vacate dates found.');

            return 0;
        }

        $this->info("Found {$vacatedRentOuts->count()} rent out(s) to vacate.");

        $updatedCount = 0;
        $failedCount = 0;

        foreach ($vacatedRentOuts as $rentOut) {
            try {
                DB::beginTransaction();

                $rentOut->update([
                    'status' => RentOutStatus::Vacated,
                ]);

                DB::commit();
                $updatedCount++;
                $customerName = $rentOut->customer?->name ?? 'N/A';
                $this->info("Vacated Rent Out ID {$rentOut->id} - Customer: {$customerName}, Vacate Date: {$rentOut->vacate_date->format('Y-m-d')}");
            } catch (\Exception $e) {
                DB::rollBack();
                $failedCount++;
                $this->error("Failed for Rent Out ID {$rentOut->id}: {$e->getMessage()}");
            }
        }

        $this->info("\nSummary:");
        $this->info("  Vacated: {$updatedCount}");
        $this->info("  Failed: {$failedCount}");

        return $failedCount > 0 ? 1 : 0;
    }
}
