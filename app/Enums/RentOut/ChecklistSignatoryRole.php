<?php

namespace App\Enums\RentOut;

enum ChecklistSignatoryRole: string
{
    case Lessee = 'lessee';
    case FacilityCoordinator = 'facility_coordinator';
    case LeasingCoordinator = 'leasing_coordinator';

    public function label(): string
    {
        return match ($this) {
            self::Lessee => 'Lessee',
            self::FacilityCoordinator => 'Facility Coordinator',
            self::LeasingCoordinator => 'Leasing Coordinator',
        };
    }
}
