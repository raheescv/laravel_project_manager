<?php

namespace App\Console\Commands\Property;

use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateRentOut extends Command
{
    protected $signature = 'property:update-rent-out';

    protected $description = 'Checks all occupied rent outs, marks expired ones if end_date has passed';

    public function handle()
    {
        $this->info('Checking for expired rent outs...');

        $expiredRentOuts = RentOut::withoutGlobalScopes()
            ->where('status', RentOutStatus::Occupied)
            ->where('end_date', '<', Carbon::today())
            ->get();

        if ($expiredRentOuts->isEmpty()) {
            $this->info('No expired rent outs found.');

            return 0;
        }

        $this->info("Found {$expiredRentOuts->count()} expired rent out(s).");

        $updatedCount = 0;
        $failedCount = 0;

        foreach ($expiredRentOuts as $rentOut) {
            try {
                DB::beginTransaction();

                $rentOut->update([
                    'status' => RentOutStatus::Expired,
                ]);

                DB::commit();
                $updatedCount++;
                $this->info("Marked Rent Out ID {$rentOut->id} as expired (end date: {$rentOut->end_date->format('Y-m-d')})");
            } catch (\Exception $e) {
                DB::rollBack();
                $failedCount++;
                $this->error("Failed for Rent Out ID {$rentOut->id}: {$e->getMessage()}");
            }
        }

        $this->info("\nSummary:");
        $this->info("  Updated to expired: {$updatedCount}");
        $this->info("  Failed: {$failedCount}");

        return $failedCount > 0 ? 1 : 0;
    }
}
