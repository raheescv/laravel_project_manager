<?php

namespace App\Console\Commands\Property;

use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RentOutExpiryCheck extends Command
{
    protected $signature = 'property:rent-out-expiry-check';

    protected $description = 'Sends notifications for rent outs expiring within 30 days';

    public function handle()
    {
        $this->info('Checking for rent outs expiring within 30 days...');

        $expiringRentOuts = RentOut::withoutGlobalScopes()
            ->where('status', RentOutStatus::Occupied)
            ->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays(30)])
            ->with(['customer', 'property', 'building'])
            ->get();

        if ($expiringRentOuts->isEmpty()) {
            $this->info('No rent outs expiring within 30 days.');

            return 0;
        }

        $this->info("Found {$expiringRentOuts->count()} rent out(s) expiring soon.");

        foreach ($expiringRentOuts as $rentOut) {
            $daysLeft = Carbon::today()->diffInDays($rentOut->end_date);
            $customerName = $rentOut->customer?->name ?? 'N/A';
            $propertyName = $rentOut->property?->name ?? 'N/A';

            $this->warn("Rent Out ID {$rentOut->id} - Customer: {$customerName}, Property: {$propertyName}, Expires in {$daysLeft} days ({$rentOut->end_date->format('Y-m-d')})");

            Log::channel('daily')->info("Rent Out Expiry Warning: ID {$rentOut->id}, Customer: {$customerName}, Property: {$propertyName}, Expires: {$rentOut->end_date->format('Y-m-d')}, Days Left: {$daysLeft}");
        }

        $this->info("\nTotal expiring: {$expiringRentOuts->count()}");

        return 0;
    }
}
