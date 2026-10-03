<?php

use App\Models\Purchase;
use App\Models\RentOut;

/**
 * The global `account_id.required` message names the customer; purchase
 * flows override it per call so sale agreements never ask for a vendor.
 */
it('asks for a customer when a sale or rent agreement has no account', function (): void {
    expect(fn () => validationHelper(RentOut::rules(), [], 'RentOut'))
        ->toThrow(Exception::class, 'Please select customer.');
});

it('asks for a vendor when a purchase has no account', function (): void {
    expect(fn () => validationHelper(Purchase::rules(), ['branch_id' => 1], null, ['account_id.required' => 'Please select vendor.']))
        ->toThrow(Exception::class, 'Please select vendor.');
});
