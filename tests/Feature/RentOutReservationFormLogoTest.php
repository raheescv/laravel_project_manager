<?php

use App\Enums\RentOut\AgreementType;
use App\Models\Configuration;
use App\Models\RentOut;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Storage;

function renderReservationForm(AgreementType $agreementType): string
{
    $rentOut = new RentOut(['agreement_type' => $agreementType]);
    $rentOut->id = 1846;

    return view('print.booking.reservation-form', [
        'rentOut' => $rentOut,
        'propertyDetails' => [],
        'buyerDetails' => [],
        'agentDetails' => [],
    ])->render();
}

function storeReservationLogo(string $key, string $contents): void
{
    Storage::disk('public')->put("rent_out_logos/{$key}.png", $contents);
    Configuration::updateOrCreate(['key' => $key], ['value' => "rent_out_logos/{$key}.png"]);
}

beforeEach(function (): void {
    app(TenantService::class)->setCurrentTenant(Tenant::factory()->create());
});

afterEach(function (): void {
    Storage::disk('public')->delete(['rent_out_logos/rental_reservation_logo.png', 'rent_out_logos/lease_reservation_logo.png', 'rent_out_logos/company_logo.png']);
});

it('prints the rental reservation logo on a rental reservation form', function (): void {
    storeReservationLogo('rental_reservation_logo', 'rental-logo');
    storeReservationLogo('lease_reservation_logo', 'sale-logo');

    $html = renderReservationForm(AgreementType::Rental);

    expect($html)->toContain(base64_encode('rental-logo'))
        ->not->toContain(base64_encode('sale-logo'));
});

it('prints the sale reservation logo on a sale reservation form', function (): void {
    storeReservationLogo('rental_reservation_logo', 'rental-logo');
    storeReservationLogo('lease_reservation_logo', 'sale-logo');

    $html = renderReservationForm(AgreementType::Lease);

    expect($html)->toContain(base64_encode('sale-logo'))
        ->not->toContain(base64_encode('rental-logo'));
});

it('falls back to the company logo when no reservation logo is set', function (): void {
    storeReservationLogo('company_logo', 'company-logo');

    expect(renderReservationForm(AgreementType::Rental))->toContain(base64_encode('company-logo'));
});
