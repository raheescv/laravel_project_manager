<?php

use App\Models\Sale;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * The contract that makes offline selling safe.
 *
 * A till that loses its connection cannot tell a lost request from a lost
 * response, so it replays a queued sale until the server acknowledges it. These
 * tests pin the two halves of the guarantee: a replay must never create a second
 * sale, and two genuinely different sales must never be mistaken for a replay.
 *
 * @see docs/mobile-offline-sale-plan.md
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Sanctum::actingAs($this->world->user);
});

it('creates a sale when a client uuid is supplied', function (): void {
    $uuid = (string) Str::uuid();

    $response = $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => $uuid,
        'clientCreatedAt' => now()->subMinutes(30)->toIso8601String(),
    ]));

    $response->assertSuccessful()->assertJsonPath('success', true);

    $sale = Sale::withoutGlobalScopes()->where('client_uuid', $uuid)->first();
    expect($sale)->not->toBeNull()
        ->and($sale->tenant_id)->toBe($this->world->tenant->id)
        ->and($sale->status)->toBe('completed');
});

it('returns the same sale instead of creating a second one when a uuid is replayed', function (): void {
    $payload = $this->world->salePayload(['clientUuid' => (string) Str::uuid()]);

    $first = $this->postJson($this->world->url('/api/v1/sale'), $payload)->assertSuccessful();
    $second = $this->postJson($this->world->url('/api/v1/sale'), $payload)->assertSuccessful();

    // Same sale, twice — this is the whole point of the idempotency key.
    expect($second->json('data.id'))->toBe($first->json('data.id'));
    expect(Sale::withoutGlobalScopes()->where('client_uuid', $payload['clientUuid'])->count())->toBe(1);
    expect(Sale::withoutGlobalScopes()->count())->toBe(1);
});

it('echoes the client uuid back so the app can retire its outbox row', function (): void {
    $uuid = (string) Str::uuid();

    $response = $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload(['clientUuid' => $uuid]))->assertSuccessful();

    expect($response->json('data.client_uuid'))->toBe($uuid);
});

it('returns the fully loaded resource on a replay, not a bare model', function (): void {
    $payload = $this->world->salePayload(['clientUuid' => (string) Str::uuid()]);

    $first = $this->postJson($this->world->url('/api/v1/sale'), $payload)->assertSuccessful();
    $replay = $this->postJson($this->world->url('/api/v1/sale'), $payload)->assertSuccessful();

    // A replay that dropped its eager loads would render empty items/payments
    // and the app would print a receipt with no lines on it.
    expect($replay->json('data'))->toEqual($first->json('data'));
    expect($replay->json('data.items'))->toHaveCount(1);
    expect($replay->json('data.payments'))->toHaveCount(1);
    expect($replay->json('data.summary.grand_total'))->toEqual($first->json('data.summary.grand_total'));
});

it('stores the till clock without letting it become the accounting date', function (): void {
    $rungUpAt = now()->subDays(1)->setTime(14, 30);

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'clientCreatedAt' => $rungUpAt->toIso8601String(),
    ]))->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->latest('id')->first();

    expect($sale->client_created_at)->not->toBeNull();
    // A device clock is never trusted for the books: the sale still belongs to
    // the server's day, which is what the day-close guard on the app protects.
    expect((string) $sale->date)->toBe(today()->toDateString());
});

it('commits two identical sales that carry different uuids', function (): void {
    // Two customers buying the same single item seconds apart. Without the
    // bypass, the 2-minute duplicate heuristic refuses the second — which is
    // exactly what happens while an offline backlog drains.
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload(['clientUuid' => (string) Str::uuid()]))->assertSuccessful();
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload(['clientUuid' => (string) Str::uuid()]))->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->count())->toBe(2);
});

it('still refuses an accidental duplicate when no uuid is supplied', function (): void {
    // The online path has no idempotency key, so the heuristic guard is all
    // that stands between a double-tapped Charge button and a double charge.
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload())->assertSuccessful();

    $second = $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload());

    expect($second->json('success'))->toBeFalse();
    expect(Sale::withoutGlobalScopes()->count())->toBe(1);
});

it('does not protect two presses of Charge that carry different uuids', function (): void {
    // Documents the consequence of the guard bypass, and why CartCubit holds one
    // key per *ticket* rather than minting a fresh one per press: with a new key
    // each time, a cashier re-pressing Charge after a visible error would commit
    // the sale twice. The server cannot tell that apart from two real customers
    // buying the same thing, so the client is what has to get this right.
    $payload = $this->world->salePayload();

    $this->postJson($this->world->url('/api/v1/sale'), $payload + ['clientUuid' => (string) Str::uuid()])->assertSuccessful();
    $this->postJson($this->world->url('/api/v1/sale'), $payload + ['clientUuid' => (string) Str::uuid()])->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->count())->toBe(2);
})->note('CartCubit keeps _chargeUuid stable until the ticket changes — see cart_cubit.dart');

it('rejects a malformed client uuid', function (): void {
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload(['clientUuid' => 'not-a-uuid']))
        ->assertStatus(422);
});

it('never resolves another tenant sale for the same uuid', function (): void {
    $uuid = (string) Str::uuid();
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload(['clientUuid' => $uuid]))->assertSuccessful();

    // A second tenant replaying the same uuid must get its own sale, not the
    // first tenant's. The replay lookup drops the branch scope, so this is the
    // test that the tenant scope was kept.
    $other = PosWorld::create();
    Sanctum::actingAs($other->user);

    $response = $this->postJson($other->url('/api/v1/sale'), $other->salePayload(['clientUuid' => $uuid]))->assertSuccessful();

    expect(Sale::withoutGlobalScopes()->where('client_uuid', $uuid)->count())->toBe(2);
    expect($response->json('data.id'))->not->toBe(
        (string) Sale::withoutGlobalScopes()->where('tenant_id', $this->world->tenant->id)->value('id')
    );
});

it('refuses to resurrect a sale that was recorded and then voided', function (): void {
    $payload = $this->world->salePayload(['clientUuid' => (string) Str::uuid()]);
    $this->postJson($this->world->url('/api/v1/sale'), $payload)->assertSuccessful();

    Sale::withoutGlobalScopes()->where('client_uuid', $payload['clientUuid'])->first()->delete();

    // The unique index counts soft-deleted rows, so re-creating is impossible
    // anyway. Failing with an explanation beats failing on a raw constraint.
    $response = $this->postJson($this->world->url('/api/v1/sale'), $payload);

    expect($response->json('success'))->toBeFalse();
    expect($response->json('message'))->toContain('voided');
    expect(Sale::withoutGlobalScopes()->withTrashed()->count())->toBe(1);
});

it('moves stock exactly once across a replay', function (): void {
    $world = PosWorld::create(stock: 10);
    Sanctum::actingAs($world->user);
    $payload = $world->salePayload(['clientUuid' => (string) Str::uuid()]);

    $this->postJson($world->url('/api/v1/sale'), $payload)->assertSuccessful();
    $this->postJson($world->url('/api/v1/sale'), $payload)->assertSuccessful();

    // The replay returns early, before StockUpdateAction — a second decrement
    // here would mean the shelf count drifts by one for every retry.
    $quantity = DB::table('inventories')
        ->where('product_id', $world->product->id)
        ->where('branch_id', $world->branch->id)
        ->value('quantity');

    expect((float) $quantity)->toBe(9.0);
});
