<?php

namespace App\Support;

use App\Enums\RentOut\AgreementType;

class RentOutConfig
{
    public bool $isRental;

    public bool $isLease;

    public string $typeKey;

    // Labels
    public string $singularLabel;

    public string $pluralLabel;

    public string $amountLabel;

    public string $unitPriceLabel;

    public string $detailsLabel;

    public string $periodLabel;

    public string $bookingLabel;

    public string $defaultTermLabel;

    public string $searchPlaceholder;

    public string $bookingSearchPlaceholder;

    public string $notFoundMessage;

    public string $emptyMessage;

    public string $bookingEmptyMessage;

    public string $subtitle;

    public string $bookingSubtitle;

    // Routes
    public string $indexRoute;

    public string $createRoute;

    public string $editRoute;

    public string $viewRoute;

    public string $bookingRoute;

    public string $bookingCreateRoute;

    public string $bookingEditRoute;

    public string $bookingViewRoute;

    // Permissions
    public string $viewPermission;

    public string $createPermission;

    public string $editPermission;

    public string $deletePermission;

    // Booking Permissions
    public string $bookingEditPermission;

    public string $bookingCancelPermission;

    public string $bookingConfirmPermission;

    public string $bookingFinancialApprovePermission;

    public string $bookingApprovePermission;

    public string $bookingCompletePermission;

    // Events
    public string $refreshEvent;

    public string $refreshTableEvent;

    public string $bookingRefreshEvent;

    public string $bookingRefreshTableEvent;

    public function __construct(public AgreementType $agreementType)
    {
        $this->isRental = $agreementType === AgreementType::Rental;
        $this->isLease = $agreementType === AgreementType::Lease;
        $this->typeKey = $agreementType->value;

        $this->initLabels();
        $this->initRoutes();
        $this->initPermissions();
        $this->initEvents();
    }

    protected function initLabels(): void
    {
        if ($this->isRental) {
            $this->singularLabel = 'Rental Agreement';
            $this->pluralLabel = 'Rental Agreements';
            $this->amountLabel = 'Rent';
            $this->unitPriceLabel = 'Rent';
            $this->detailsLabel = 'Rent Details';
            $this->periodLabel = 'Rental Period';
            $this->bookingLabel = 'Rental Booking';
            $this->defaultTermLabel = 'rent payment';
            $this->searchPlaceholder = 'Search rentals...';
            $this->bookingSearchPlaceholder = 'Search bookings...';
            $this->notFoundMessage = 'Rental agreement not found.';
            $this->emptyMessage = 'No rental agreements found matching your search.';
            $this->bookingEmptyMessage = 'No rental bookings found matching your search.';
            $this->subtitle = 'Manage rental agreements and tenancy contracts';
            $this->bookingSubtitle = 'Manage rental bookings';
        } else {
            $this->singularLabel = 'Sale Agreement';
            $this->pluralLabel = 'Sale Agreements';
            $this->amountLabel = 'Total';
            $this->unitPriceLabel = 'Unit Sale Price';
            $this->detailsLabel = 'Sale Details';
            $this->periodLabel = 'Period';
            $this->bookingLabel = 'Sale Booking';
            $this->defaultTermLabel = 'installment';
            $this->searchPlaceholder = 'Search sale agreements...';
            $this->bookingSearchPlaceholder = 'Search bookings...';
            $this->notFoundMessage = 'Sale agreement not found.';
            $this->emptyMessage = 'No sale agreements found matching your search.';
            $this->bookingEmptyMessage = 'No sale bookings found matching your search.';
            $this->subtitle = 'Manage sale agreements';
            $this->bookingSubtitle = 'Manage sale bookings';
        }
    }

    protected function initRoutes(): void
    {
        $prefix = $this->isRental ? 'property::rent::' : 'property::sale::';
        $this->indexRoute = $prefix.'index';
        $this->createRoute = $prefix.'create';
        $this->editRoute = $prefix.'edit';
        $this->viewRoute = $prefix.'view';
        $this->bookingRoute = $prefix.'booking';
        $this->bookingCreateRoute = $prefix.'booking.create';
        $this->bookingEditRoute = $prefix.'booking.edit';
        $this->bookingViewRoute = $prefix.'booking.view';
    }

    protected function initPermissions(): void
    {
        $prefix = $this->isRental ? 'rent out' : 'rent out lease';
        $this->viewPermission = $prefix.'.view';
        $this->createPermission = $prefix.'.create';
        $this->editPermission = $prefix.'.edit';
        $this->deletePermission = $prefix.'.delete';

        $bookingPrefix = $this->isRental ? 'rent out booking' : 'rent out lease booking';
        $this->bookingEditPermission = $bookingPrefix.'.edit';
        $this->bookingCancelPermission = $bookingPrefix.'.cancel';
        $this->bookingConfirmPermission = $bookingPrefix.'.confirm';
        $this->bookingFinancialApprovePermission = $bookingPrefix.'.financial approved';
        $this->bookingApprovePermission = $bookingPrefix.'.approved';
        $this->bookingCompletePermission = $bookingPrefix.'.completed';
    }

    protected function initEvents(): void
    {
        $key = $this->isRental ? 'Rent' : 'Sale';
        $this->refreshEvent = "RentOut{$key}-Refresh-Component";
        $this->refreshTableEvent = "RefreshRentOut{$key}Table";
        $this->bookingRefreshEvent = "RentOut{$key}Booking-Refresh-Component";
        $this->bookingRefreshTableEvent = "RefreshRentOut{$key}BookingTable";
    }

    public static function make(string $type): self
    {
        $agreementType = AgreementType::from($type);

        return new self($agreementType);
    }
}
