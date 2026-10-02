<?php

use App\Enums\RentOut\AgreementType;
use App\Models\RentOut;
use App\Models\RentOutTransaction;

it('labels the rent out receipt by the agreement type', function (AgreementType $type, string $expected, string $unexpected): void {
    $rentOut = new RentOut();
    $rentOut->agreement_type = $type;

    $payment = new RentOutTransaction(['credit' => 100, 'debit' => 0]);
    $payment->id = 1;

    $html = view('print.rentout.receipt', [
        'payment' => $payment,
        'rentOut' => $rentOut,
        'companyName' => 'Acme',
        'companyAddress' => null,
        'companyPhone' => null,
        'companyEmail' => null,
        'companyLogo' => null,
    ])->render();

    expect($html)->toContain($expected)->not->toContain($unexpected);
})->with([
    'rental' => [AgreementType::Rental, 'Rental Income', 'Sale Income'],
    'lease' => [AgreementType::Lease, 'Sale Income', 'Rental Income'],
]);
