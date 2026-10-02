<?php

use App\Models\RentOut;
use App\Models\RentOutTransaction;

/**
 * @param  array<string, mixed>  $attributes
 */
function renderRentOutReceipt(?string $model, array $attributes = [], string $view = 'print.rentout.receipt'): string
{
    $rentOut = new RentOut(['agreement_no' => 'AG-1']);
    $rentOut->setRelation('customer', null);
    $rentOut->setRelation('property', null);
    $rentOut->setRelation('building', null);

    $payment = new RentOutTransaction(array_merge(['model' => $model, 'credit' => 1000, 'debit' => 0], $attributes));
    $payment->id = 76328;
    $payment->setRelation('account', null);

    return view($view, [
        'payment' => $payment,
        'rentOut' => $rentOut,
        'companyName' => 'Astra',
        'companyPhone' => null,
        'companyAddress' => null,
        'companyEmail' => null,
        'companyWebsite' => null,
        'companyLogo' => null,
    ])->render();
}

it('titles a security deposit receipt as a security deposit receipt voucher', function (): void {
    expect(renderRentOutReceipt('RentOutSecurity'))->toContain('Security Deposit Receipt Voucher');
});

it('keeps the plain receipt voucher title for other payments', function (): void {
    expect(renderRentOutReceipt('RentOutPaymentTerm'))
        ->toContain('Receipt Voucher')
        ->not->toContain('Security Deposit Receipt Voucher');
});

it('titles a service charge (debit) row as a category invoice', function (): void {
    $html = renderRentOutReceipt('RentOutService', ['source' => 'Service', 'category' => 'gym', 'credit' => 0, 'debit' => 1000]);

    expect($html)
        ->toContain('Gym Invoice')
        ->toContain('Amount Due')
        ->not->toContain('Receipt Voucher');
});

it('keeps the receipt voucher for a service payment (credit) row', function (): void {
    $html = renderRentOutReceipt('RentOutService', ['source' => 'Service', 'category' => 'gym', 'credit' => 1000, 'debit' => 0]);

    expect($html)
        ->toContain('Receipt Voucher')
        ->toContain('Amount Received')
        ->not->toContain('Gym Invoice');
});

it('uses the payment voucher header layout', function (): void {
    expect(renderRentOutReceipt(null))
        ->toContain('class="b-eyebrow">Accounts Receivable')
        ->not->toContain('logo-box');
});

it('titles a service charge (debit) voucher as a category invoice', function (): void {
    $html = renderRentOutReceipt('RentOutService', ['source' => 'Service', 'category' => 'gym', 'credit' => 0, 'debit' => 1000], 'print.rentout.voucher');

    expect($html)
        ->toContain('Gym Invoice')
        ->toContain('Amount Due')
        ->not->toContain('Payment Voucher');
});

it('titles a plain debit voucher as a payment voucher', function (): void {
    $html = renderRentOutReceipt(null, ['credit' => 0, 'debit' => 1000], 'print.rentout.voucher');

    expect($html)
        ->toContain('Payment Voucher')
        ->toContain('Amount Paid')
        ->not->toContain('Invoice');
});

it('titles a security deposit voucher as a security deposit payment voucher', function (): void {
    expect(renderRentOutReceipt('RentOutSecurity', ['credit' => 0, 'debit' => 1000], 'print.rentout.voucher'))
        ->toContain('Security Deposit Payment Voucher');
});
