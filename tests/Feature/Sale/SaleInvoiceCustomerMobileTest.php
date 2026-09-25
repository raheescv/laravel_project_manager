<?php

use App\Actions\Sale\CreateAction;
use App\Helpers\SaleHelper;
use App\Models\Account;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosWorld;

/**
 * Settings → Sale → "Enable Customer Mobile In Print" decides whether the
 * customer's mobile number appears on the printed sale invoice. The Arabic
 * layout labels the row in both languages.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Account::withoutGlobalScopes()->whereKey($this->world->accounts['general_customer'])->update(['mobile' => '33647110']);

    $inventoryId = DB::table('inventories')
        ->where('product_id', $this->world->product->id)
        ->where('branch_id', $this->world->branch->id)
        ->value('id');

    $response = (new CreateAction())->execute([
        'branch_id' => $this->world->branch->id,
        'account_id' => $this->world->accounts['general_customer'],
        'date' => today()->toDateString(),
        'sale_type' => 'normal',
        'status' => 'completed',
        'gross_amount' => 50,
        'item_discount' => 0,
        'tax_amount' => 0,
        'other_discount' => 0,
        'freight' => 0,
        'round_off' => 0,
        'paid' => 50,
        'items' => [[
            'inventory_id' => $inventoryId,
            'product_id' => $this->world->product->id,
            'unit_id' => $this->world->product->unit_id,
            'employee_id' => $this->world->user->id,
            'unit_price' => 50,
            'quantity' => 1,
            'conversion_factor' => 1,
            'discount' => 0,
            'tax' => 0,
        ]],
        'payments' => [[
            'payment_method_id' => $this->world->cashAccountId,
            'amount' => 50,
            'date' => today()->toDateString(),
        ]],
        'comboOffers' => [],
    ], $this->world->user->id);

    $this->saleId = $response['data']->id;
});

it('prints the customer mobile with its arabic label by default', function (string $style): void {
    Configuration::updateOrCreate(['key' => 'thermal_printer_style'], ['value' => $style]);

    $html = (new SaleHelper)->saleInvoice($this->saleId);

    expect($html)->toContain('33647110');
    if ($style === 'with_arabic') {
        expect($html)->toContain('الجوال');
    }
})->with(['with_arabic', 'english_only']);

it('hides the customer mobile when the setting is off', function (string $style): void {
    Configuration::updateOrCreate(['key' => 'thermal_printer_style'], ['value' => $style]);
    Configuration::updateOrCreate(['key' => 'enable_customer_mobile_in_print'], ['value' => 'no']);

    $html = (new SaleHelper)->saleInvoice($this->saleId);

    expect($html)->not->toContain('33647110')
        ->and($html)->not->toContain('الجوال');
})->with(['with_arabic', 'english_only']);
