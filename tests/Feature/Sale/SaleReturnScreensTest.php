<?php

use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Actions\Sale\UpdateAction as SaleUpdateAction;
use App\Actions\SaleReturn\CreateAction as SaleReturnCreateAction;
use App\Livewire\SaleReturn\ColumnVisibility;
use App\Livewire\SaleReturn\Page as SaleReturnPage;
use App\Livewire\SaleReturn\Table as SaleReturnTable;
use App\Livewire\SaleReturn\View as SaleReturnView;
use App\Models\Account;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Sale return screens that crashed in production for tenants other than the
 * first: no saved column choices, no configured default payment method, view
 * buttons wired to methods the component never had, and a POS edit without `paid`.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 100, price: 50);
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

/**
 * @return array<string, mixed>
 */
function saleReturnScreensPayload(PosWorld $world, float $amount): array
{
    $inventoryId = DB::table('inventories')
        ->where('product_id', $world->product->id)
        ->where('branch_id', $world->branch->id)
        ->value('id');

    return [
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => today()->toDateString(),
        'sale_type' => 'normal',
        'status' => 'completed',
        'gross_amount' => $amount,
        'item_discount' => 0,
        'tax_amount' => 0,
        'other_discount' => 0,
        'freight' => 0,
        'round_off' => 0,
        'paid' => $amount,
        'items' => [[
            'inventory_id' => $inventoryId,
            'product_id' => $world->product->id,
            'unit_id' => $world->product->unit_id,
            'employee_id' => $world->user->id,
            'unit_price' => $amount,
            'quantity' => 1,
            'conversion_factor' => 1,
            'discount' => 0,
            'tax' => 0,
        ]],
        'payments' => [['payment_method_id' => $world->cashAccountId, 'amount' => $amount]],
        'comboOffers' => [],
    ];
}

it('renders the sale return list with no saved column choices', function (): void {
    expect(Configuration::where('key', 'sale_return_visible_column')->exists())->toBeFalse();

    Livewire::test(SaleReturnTable::class)
        ->assertOk()
        ->assertSet('sale_return_visible_column.customer', true);
});

it('keeps saved sale return columns and fills in ones saved before they existed', function (): void {
    Configuration::create([
        'tenant_id' => $this->world->tenant->id,
        'key' => 'sale_return_visible_column',
        'value' => json_encode(['customer' => false]),
    ]);

    $columns = ColumnVisibility::visibleColumns();

    expect($columns['customer'])->toBeFalse()
        ->and($columns['grand_total'])->toBeTrue();
});

it('opens the sale return page when the tenant never set a default payment method', function (): void {
    expect(Configuration::where('key', 'default_payment_method_id')->exists())->toBeFalse();

    Livewire::test(SaleReturnPage::class)
        ->assertOk()
        ->assertSet('default_payment_method_id', $this->world->cashAccountId)
        ->assertSet('payment_method_name', 'cash');
});

it('falls back to the tenant payment method when the configured one belongs to another tenant', function (): void {
    $otherTenantAccountId = Account::withoutGlobalScopes()->where('tenant_id', '!=', $this->world->tenant->id)->value('id') ?? PHP_INT_MAX;

    expect(Account::resolvePaymentMethod($otherTenantAccountId)?->id)->toBe($this->world->cashAccountId)
        ->and(Account::resolvePaymentMethod(null)?->id)->toBe($this->world->cashAccountId)
        ->and(Account::resolvePaymentMethod($this->world->accounts['card'])?->id)->toBe($this->world->accounts['card']);
});

it('shows no sale return actions the view component cannot handle', function (): void {
    foreach (['sales return.cancel', 'sales return.edit completed'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    $sale = (new SaleCreateAction())->execute(saleReturnScreensPayload($this->world, 50), $this->world->user->id)['data'];
    $payload = saleReturnScreensPayload($this->world, 50);
    $payload['sale_id'] = $sale->id;
    $created = (new SaleReturnCreateAction())->execute($payload, $this->world->user->id);
    expect($created['success'])->toBeTrue($created['message']);

    Livewire::test(SaleReturnView::class, ['table_id' => $created['data']->id])
        ->assertOk()
        ->assertDontSeeHtml("wire:click='save(")
        ->assertDontSeeHtml("wire:click='sendToWhatsapp'");
});

it('updates a sale whose payload carries no paid amount', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'sale.edit completed', 'guard_name' => 'web']));
    $sale = (new SaleCreateAction())->execute(saleReturnScreensPayload($this->world, 50), $this->world->user->id)['data']->refresh();
    expect((float) $sale->paid)->toBe(50.0);

    $payload = saleReturnScreensPayload($this->world, 50);
    unset($payload['paid']);
    $payload['total'] = 50;
    $payload['items'] = $sale->items()->get()->map(fn ($item) => $item->toArray())->all();
    $payload['payments'] = $sale->payments()->get()->map(fn ($payment) => $payment->toArray())->all();

    $response = (new SaleUpdateAction())->execute($payload, $sale->id, $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        ->and((float) $sale->refresh()->paid)->toBe(50.0);
});
