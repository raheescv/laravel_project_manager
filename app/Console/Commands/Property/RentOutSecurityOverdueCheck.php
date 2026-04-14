<?php

namespace App\Console\Commands\Property;

use App\Enums\RentOut\SecurityStatus;
use App\Models\RentOutSecurity;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RentOutSecurityOverdueCheck extends Command
{
    protected $signature = 'property:security-overdue-check';

    protected $description = 'Checks for overdue security deposits';

    public function handle()
    {
        $this->info('Checking for overdue security deposits...');

        $overdueSecurities = RentOutSecurity::withoutGlobalScopes()
            ->where('status', SecurityStatus::Pending)
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::today())
            ->with(['rentOut.customer', 'rentOut.property'])
            ->get();

        if ($overdueSecurities->isEmpty()) {
            $this->info('No overdue security deposits found.');

            return 0;
        }

        $this->info("Found {$overdueSecurities->count()} overdue security deposit(s).");

        foreach ($overdueSecurities as $security) {
            $customerName = $security->rentOut?->customer?->name ?? 'N/A';
            $propertyName = $security->rentOut?->property?->name ?? 'N/A';
            $daysOverdue = Carbon::today()->diffInDays($security->due_date);

            $this->warn("Security ID {$security->id} - Rent Out ID {$security->rent_out_id}, Customer: {$customerName}, Property: {$propertyName}, Amount: {$security->amount}, Overdue by {$daysOverdue} days");

            Log::channel('daily')->warning("Overdue Security Deposit: ID {$security->id}, Rent Out: {$security->rent_out_id}, Customer: {$customerName}, Amount: {$security->amount}, Due: {$security->due_date->format('Y-m-d')}, Overdue: {$daysOverdue} days");
        }

        $this->info("\nTotal overdue: {$overdueSecurities->count()}");

        return 0;
    }
}
