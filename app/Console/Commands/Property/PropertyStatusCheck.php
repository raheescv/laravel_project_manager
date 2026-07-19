<?php

namespace App\Console\Commands\Property;

use App\Enums\Property\PropertyStatus;
use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\RentOutStatus;
use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PropertyStatusCheck extends Command
{
    protected $signature = 'property:status-check';

    protected $description = 'Syncs property occupancy status and availability status based on active rent outs';

    public function handle()
    {
        $this->info('Syncing property statuses based on active rent outs...');

        $properties = Property::withoutGlobalScopes()
            ->with(['rentOuts' => function ($query) {
                $query->whereIn('status', [
                    RentOutStatus::Occupied,
                    RentOutStatus::Booked,
                ]);
            }])
            ->get();

        if ($properties->isEmpty()) {
            $this->info('No properties found.');

            return 0;
        }

        $updatedCount = 0;
        $failedCount = 0;

        foreach ($properties as $property) {
            try {
                $hasOccupied = $property->rentOuts->contains('status', RentOutStatus::Occupied);
                $hasBooked = $property->rentOuts->contains('status', RentOutStatus::Booked);

                $newStatus = PropertyStatus::Vacant;
                if ($hasOccupied) {
                    $newStatus = PropertyStatus::Occupied;
                } elseif ($hasBooked) {
                    $newStatus = PropertyStatus::Booked;
                }

                // Availability: a property is "sold" while it has an active
                // Lease (= Sale) agreement, otherwise it is "available".
                $hasActiveSale = $property->rentOuts->contains('agreement_type', AgreementType::Lease);
                $newAvailability = $hasActiveSale ? 'sold' : 'available';

                $changes = [];
                if ($property->status !== $newStatus) {
                    $changes['status'] = $newStatus->value;
                }
                if ($property->availability_status !== $newAvailability) {
                    $changes['availability_status'] = $newAvailability;
                }

                if (! empty($changes)) {
                    DB::beginTransaction();
                    $property->update($changes);
                    DB::commit();
                    $updatedCount++;

                    $parts = [];
                    if (isset($changes['status'])) {
                        $parts[] = "status → {$newStatus->label()}";
                    }
                    if (isset($changes['availability_status'])) {
                        $parts[] = "availability → {$newAvailability}";
                    }
                    $this->info("Property ID {$property->id} ({$property->name}): ".implode(', ', $parts));
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $failedCount++;
                $this->error("Failed for Property ID {$property->id}: {$e->getMessage()}");
            }
        }

        $this->info("\nSummary:");
        $this->info("  Total properties checked: {$properties->count()}");
        $this->info("  Updated: {$updatedCount}");
        $this->info("  Failed: {$failedCount}");

        return $failedCount > 0 ? 1 : 0;
    }
}
