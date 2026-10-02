<?php

use App\Models\Account;
use App\Models\RentOutTransaction;
use App\Models\Tenant;
use App\Services\TenantService;

it('prints the category in formal words', function (?string $category, string $expected): void {
    $payment = new RentOutTransaction(['category' => $category]);

    expect($payment->category_label)->toBe($expected);
})->with([
    'snake case slug' => ['management_fee', 'Management Fee'],
    'single word' => ['rent', 'Rent'],
    'already formal' => ['Security Deposit', 'Security Deposit'],
    'empty' => [null, ''],
]);

it('prints the income account name when the category stores an account id', function (): void {
    $tenant = Tenant::create(['name' => 'Receipt Tenant', 'subdomain' => 'rcpt'.uniqid(), 'is_active' => 1]);
    app(TenantService::class)->setCurrentTenant($tenant);

    $account = Account::create(['tenant_id' => $tenant->id, 'account_type' => 'income', 'name' => 'Service Charge Income']);

    $payment = new RentOutTransaction(['category' => (string) $account->id]);

    expect($payment->category_label)->toBe('Service Charge Income');
});
