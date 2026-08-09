<?php

use App\Models\Sale;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * A sale taken offline belongs to the cashier who served the customer, not to
 * whoever happens to be signed in when the queue finally drains.
 *
 * A shared till is signed in and out all day, so without this the takings would
 * be filed under the wrong person — and the alternative, refusing to drain
 * another cashier's rows, would strand real money on the device until they came
 * back. The claim is honoured, but only after it is checked.
 *
 * @see docs/mobile-offline-sale-plan.md
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Sanctum::actingAs($this->world->user);
});

it('files the sale under the poster when no cashier is claimed', function (): void {
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload())->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->value('created_by'))->toBe($this->world->user->id);
});

it('files an offline sale under the cashier who took it, not the one who synced it', function (): void {
    $cashier = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
    ]);

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'clientUserId' => $cashier->id,
    ]))->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->first();
    expect($sale->created_by)->toBe($cashier->id)
        ->and($sale->branch_id)->toBe($this->world->branch->id);
});

it('ignores a claimed cashier from another tenant', function (): void {
    // The tenant scope on the lookup is what stops one business posting sales
    // under another business's staff.
    $stranger = PosWorld::create()->user;
    Sanctum::actingAs($this->world->user);

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'clientUserId' => $stranger->id,
    ]))->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->where('tenant_id', $this->world->tenant->id)->value('created_by'))
        ->toBe($this->world->user->id);
});

it('ignores a claimed cashier who is no longer active', function (): void {
    $leaver = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
        'is_active' => 0,
    ]);

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'clientUserId' => $leaver->id,
    ]))->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->value('created_by'))->toBe($this->world->user->id);
});

it('ignores a claimed cashier id that does not exist', function (): void {
    // An unrecognised claim must never cost the sale — the takings are real
    // either way, so it falls back rather than refusing.
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'clientUserId' => 999999,
    ]))->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->value('created_by'))->toBe($this->world->user->id);
});

it('takes the branch from the claimed cashier, never from the request', function (): void {
    // The branch is not a claim at all — it follows whoever the sale is filed
    // under. That is what stops this being a way to post a sale into a branch
    // you have no business in.
    $second = $this->world->addBranch();
    $cashierAtSecond = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $second->id,
    ]);

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'clientUserId' => $cashierAtSecond->id,
        // Ignored outright — the request has no say in the branch.
        'clientBranchId' => $this->world->branch->id,
    ]))->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->first();
    expect($sale->created_by)->toBe($cashierAtSecond->id)
        ->and($sale->branch_id)->toBe($second->id);
});

it('lets one cashier drain another cashier queue without misfiling it', function (): void {
    // The whole point: cashier A took the sales, B is signed in when the network
    // returns, and the till syncs anyway with A's name on them.
    $cashierA = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
    ]);
    $cashierB = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
    ]);

    Sanctum::actingAs($cashierB);
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'clientUserId' => $cashierA->id,
    ]))->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->value('created_by'))->toBe($cashierA->id);
});
