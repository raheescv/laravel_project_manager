<?php

use App\Actions\V1\Report\OverviewAction;
use App\Exports\PurchaseItemReportExport;
use App\Jobs\Middleware\RunInTenant;
use App\Livewire\Report\Sale\MonthlySaleReport;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SalePayment;
use App\Models\SaleReturnPayment;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * sale_payments, sale_return_payments and purchase_items carry no tenant_id,
 * so reports reach them through withinCurrentTenant(); queued exports have no
 * request, so they run inside RunInTenant. These pin that another tenant's
 * rows never reach a report — including with "All branches" selected.
 */
function seedTenantReportRows(PosWorld $world, float $amount): void
{
    $tenantId = $world->tenant->id;
    $userId = $world->user->id;
    $stamps = ['created_at' => now(), 'updated_at' => now()];

    $saleId = DB::table('sales')->insertGetId([
        'tenant_id' => $tenantId,
        'invoice_no' => 'INV'.Str::upper(Str::random(6)),
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => now()->toDateString(),
        'gross_amount' => $amount,
        'status' => 'completed',
        'created_by' => $userId,
    ] + $stamps);
    DB::table('sale_payments')->insert([
        'sale_id' => $saleId,
        'payment_method_id' => $world->cashAccountId,
        'date' => now()->toDateString(),
        'amount' => $amount,
        'created_by' => $userId,
        'updated_by' => $userId,
    ] + $stamps);

    $returnId = DB::table('sale_returns')->insertGetId([
        'tenant_id' => $tenantId,
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => now()->toDateString(),
        'status' => 'completed',
        'created_by' => $userId,
    ] + $stamps);
    DB::table('sale_return_payments')->insert([
        'sale_return_id' => $returnId,
        'payment_method_id' => $world->cashAccountId,
        'date' => now()->toDateString(),
        'amount' => $amount / 10,
        'created_by' => $userId,
        'updated_by' => $userId,
    ] + $stamps);

    $purchaseId = DB::table('purchases')->insertGetId([
        'tenant_id' => $tenantId,
        'branch_id' => $world->branch->id,
        'account_id' => $world->cashAccountId,
        'invoice_no' => 'PB-'.uniqid(),
        'date' => now()->toDateString(),
        'status' => 'completed',
        'created_by' => $userId,
    ] + $stamps);
    DB::table('purchase_items')->insert([
        'purchase_id' => $purchaseId,
        'product_id' => $world->product->id,
        'unit_id' => $world->product->unit_id,
        'unit_price' => $amount,
        'quantity' => 1,
        'discount' => 0,
        'tax' => 0,
        'created_by' => $userId,
        'updated_by' => $userId,
    ] + $stamps);
}

beforeEach(function (): void {
    $this->otherWorld = PosWorld::create();
    seedTenantReportRows($this->otherWorld, 900);

    $this->world = PosWorld::create();
    seedTenantReportRows($this->world, 100);

    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

it('narrows tenantless child tables to the current tenant', function (): void {
    expect(SalePayment::withinCurrentTenant()->pluck('amount')->map(fn ($amount) => (float) $amount)->all())->toBe([100.0])
        ->and(SaleReturnPayment::withinCurrentTenant()->pluck('amount')->map(fn ($amount) => (float) $amount)->all())->toBe([10.0])
        ->and(PurchaseItem::withinCurrentTenant()->pluck('unit_price')->map(fn ($price) => (float) $price)->all())->toBe([100.0])
        ->and(SalePayment::count())->toBe(2);
});

it('keeps other tenants out of the mobile overview payments across all branches', function (): void {
    $payments = (new OverviewAction())->execute(now()->toDateString(), now()->toDateString(), null)['payments'];

    expect((float) $payments['sales_total'])->toBe(100.0)
        ->and((float) $payments['returns_total'])->toBe(10.0);
});

it('keeps other tenants out of the monthly sale report payments across all branches', function (): void {
    $total = Livewire::test(MonthlySaleReport::class)
        ->set('filter_branch_id', '')
        ->call('fetchReport')
        ->viewData('total');

    expect((float) $total['paid_total'])->toBe(100.0);
});

it('keeps other tenants out of the purchase item export across all branches', function (): void {
    $rows = (new PurchaseItemReportExport(['branch_id' => '']))->query()->get();

    expect($rows)->toHaveCount(1)
        ->and((float) $rows->first()->unit_price)->toBe(100.0);
});

it('runs a queued job inside its tenant and restores the previous one afterwards', function (): void {
    $tenantService = app(TenantService::class);
    $seenTenantId = null;
    $seenProducts = null;

    (new RunInTenant($this->otherWorld->tenant->id))->handle(new stdClass(), function () use ($tenantService, &$seenTenantId, &$seenProducts): void {
        $seenTenantId = $tenantService->getCurrentTenantId();
        $seenProducts = Product::pluck('id')->all();
    });

    expect($seenTenantId)->toBe($this->otherWorld->tenant->id)
        ->and($seenProducts)->toContain($this->otherWorld->product->id)
        ->not->toContain($this->world->product->id)
        ->and($tenantService->getCurrentTenantId())->toBe($this->world->tenant->id);
});
