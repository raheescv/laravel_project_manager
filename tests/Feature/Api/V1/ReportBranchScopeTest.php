<?php

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The By Stylist and By Item breakdowns follow the branch the app is operating
 * as. The client attaches `branch_id` to every request and the overview already
 * honoured it, but the two ranked breakdowns query line items joined to their
 * headers — out of reach of AssignedBranchScope — so a branch switch on the
 * Reports screen kept showing every branch's stylists and products.
 *
 * @see App\Actions\V1\Report\GetAction
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->second = $this->world->addBranch();

    // A sale always lands in its cashier's default branch, so the second
    // branch's ticket is rung up by a cashier assigned there.
    $this->secondCashier = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->second->id,
        'is_admin' => 1,
    ]);

    foreach (['report.sale item', 'report.sales overview'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate([
            'tenant_id' => $this->world->tenant->id,
            'name' => $name,
            'guard_name' => 'web',
        ]));
    }

    // QAR 100 (2 × 50) at the main branch, QAR 50 (1 × 50) at the second.
    $post = function (User $user, float $quantity): void {
        Sanctum::actingAs($user);
        $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
            'clientUuid' => (string) Str::uuid(),
            'items' => [[
                'productId' => $this->world->product->id,
                'quantity' => $quantity,
                'unitPrice' => (float) $this->world->product->mrp,
                'discount' => 0,
            ]],
            'totalPayment' => $quantity * (float) $this->world->product->mrp,
        ]))->assertSuccessful();
    };

    $post($this->world->user, 2);
    $post($this->secondCashier, 1);

    Sanctum::actingAs($this->world->user);

    $this->report = fn (array $query) => $this->getJson(
        $this->world->url('/api/v1/admin/reports?'.http_build_query($query))
    )->assertSuccessful()->json('data');
});

it('ranks only the stylists of the requested branch', function (): void {
    $main = ($this->report)(['type' => 'employeewise', 'branch_id' => $this->world->branch->id]);
    $second = ($this->report)(['type' => 'employeewise', 'branch_id' => $this->second->id]);

    expect($main['rows'])->toHaveCount(1)
        ->and($main['rows'][0]['employee_name'])->toBe($this->world->user->name)
        ->and($main['summary']['total_revenue'])->toEqual(100.0)
        ->and($second['rows'])->toHaveCount(1)
        ->and($second['rows'][0]['employee_name'])->toBe($this->secondCashier->name)
        ->and($second['summary']['total_revenue'])->toEqual(50.0);
});

it('totals only the items sold at the requested branch', function (): void {
    $main = ($this->report)(['type' => 'itemwise', 'branch_id' => $this->world->branch->id]);
    $second = ($this->report)(['type' => 'itemwise', 'branch_id' => $this->second->id]);

    expect($main['summary']['total_quantity'])->toEqual(2.0)
        ->and($main['summary']['total_amount'])->toEqual(100.0)
        ->and($second['summary']['total_quantity'])->toEqual(1.0)
        ->and($second['summary']['total_amount'])->toEqual(50.0);
});

it('spans every branch when no branch is sent', function (): void {
    $employees = ($this->report)(['type' => 'employeewise']);
    $items = ($this->report)(['type' => 'itemwise']);

    expect($employees['rows'])->toHaveCount(2)
        ->and($employees['summary']['total_revenue'])->toEqual(150.0)
        ->and($items['summary']['total_quantity'])->toEqual(3.0);
});
