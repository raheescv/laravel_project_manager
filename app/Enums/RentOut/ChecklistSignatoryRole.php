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

    /**
     * Same people, different titles depending on the agreement: a rental is
     * handled by leasing and facilities, while a lease/sale hand-over is
     * signed off by admin and the site engineer.
     */
    public function labelFor(?AgreementType $agreementType): string
    {
        if ($agreementType === AgreementType::Rental) {
            return $this->label();
        }

        return match ($this) {
            self::FacilityCoordinator => 'Site Engineer',
            self::LeasingCoordinator => 'Admin Coordinator',
            default => $this->label(),
        };
    }
}
