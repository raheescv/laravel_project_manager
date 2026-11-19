<?php

use App\Livewire\Report\Sale\DailySalesInsightsReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * /report/daily_sales_insights — sale_payments carries no tenant_id, so its
 * queries must be narrowed through the sale. These pin that another tenant's
 * sales and payments never reach the report, even with "All branches".
 *
 * @see App\Livewire\Report\Sale\DailySalesInsightsReport
 */
function recordInsightSale(PosWorld $world, float $grandTotal, float $paid, string $paidOn, ?int $paymentMethodId = null): void
{
    $saleId = DB::table('sales')->insertGetId([
        'tenant_id' => $world->tenant->id,
        'invoice_no' => 'INV'.Str::upper(Str::random(6)),
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => now()->toDateString(),
        'gross_amount' => $grandTotal,
        'status' => 'completed',
        'created_by' => $world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('sale_payments')->insert([
        'sale_id' => $saleId,
        'payment_method_id' => $paymentMethodId ?? $world->cashAccountId,
        'date' => $paidOn,
        'amount' => $paid,
        'created_by' => $world->user->id,
        'updated_by' => $world->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('only reports sales and payments of the current tenant across all branches', function (): void {
    $otherWorld = PosWorld::create();
    recordInsightSale($otherWorld, 900, 900, now()->toDateString());
    recordInsightSale($otherWorld, 500, 500, now()->subDay()->toDateString());

    $world = PosWorld::create();
    recordInsightSale($world, 100, 60, now()->toDateString());

    $this->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);

    $component = Livewire::test(DailySalesInsightsReport::class)
        ->set('from_date', now()->subDays(2)->toDateString())
        ->set('branch_id', '');

    $rows = collect($component->viewData('data'));
    $total = $component->viewData('total');

    expect($rows)->toHaveCount(1)
        ->and($rows->pluck('branch_name')->all())->toBe([$world->branch->name])
        ->and((float) $total['total_sales'])->toBe(100.0)
        ->and((float) $total['paid'])->toBe(60.0)
        ->and((float) $total['credit'])->toBe(40.0);
});

it('narrows the report to invoices paid by the chosen payment method', function (): void {
    $world = PosWorld::create();
    $cardId = $world->addPaymentMethod('Card');
    recordInsightSale($world, 100, 100, now()->toDateString());
    recordInsightSale($world, 250, 200, now()->toDateString(), $cardId);

    $this->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);

    $component = Livewire::test(DailySalesInsightsReport::class)
        ->set('from_date', now()->subDays(2)->toDateString());

    expect((float) $component->viewData('total')['total_sales'])->toBe(350.0)
        ->and(collect($component->viewData('paymentMethodColumns'))->pluck('name')->all())->toContain('Cash', 'Card');

    $component->set('payment_method_id', $cardId);
    $total = $component->viewData('total');

    expect(collect($component->viewData('paymentMethodColumns'))->pluck('name')->all())->toBe(['Card'])
        ->and((float) $total['total_sales'])->toBe(250.0)
        ->and((float) $total['no_of_invoices'])->toBe(1.0)
        ->and((float) $total['paid'])->toBe(200.0)
        ->and((float) $total['credit'])->toBe(50.0)
        ->and((float) $total['total_card'])->toBe(200.0)
        ->and($total)->not->toHaveKey('total_cash');
});

it('exports the report to excel for users allowed to export sales', function (): void {
    $world = PosWorld::create();
    $world->user->update(['is_admin' => 0]);
    recordInsightSale($world, 100, 60, now()->toDateString());

    $this->actingAs($world->user);
    session(['branch_id' => $world->branch->id]);

    Livewire::test(DailySalesInsightsReport::class)
        ->call('export')
        ->assertForbidden();

    $world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'sale.export', 'guard_name' => 'web']));
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    Livewire::test(DailySalesInsightsReport::class)
        ->assertSeeHtml('wire:click="export"')
        ->assertDontSeeHtml('window.print()')
        ->call('export')
        ->assertFileDownloaded();
});
