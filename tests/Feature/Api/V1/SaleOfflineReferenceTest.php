<?php

use App\Models\Sale;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * A sale rung up offline is receipted on the spot, under a reference the device
 * invented because there was no server to ask for an invoice number. That number
 * is the only one the customer has.
 *
 * When the sale finally syncs it is given a real invoice number and the device
 * deletes its copy of the queued row — so if the server does not keep the printed
 * reference, that receipt becomes untraceable at the exact moment the sale
 * becomes real. These tests pin that it is kept, and that it can be searched.
 *
 * @see docs/mobile-offline-sale-plan.md
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Sanctum::actingAs($this->world->user);
});

it('stores the printed offline reference on the sale', function (): void {
    $uuid = (string) Str::uuid();

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => $uuid,
        'offlineRef' => 'OFF-7K2-0042',
    ]))->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->where('client_uuid', $uuid)->first();
    expect($sale->reference_no)->toBe('OFF-7K2-0042')
        // And it is NOT the invoice number: the sale gets a real one of its own,
        // which is what the books and every report key off.
        ->and($sale->invoice_no)->not->toBe('OFF-7K2-0042')
        ->and($sale->invoice_no)->not->toBeEmpty();
});

it('finds the sale by the number on the customer receipt', function (): void {
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'offlineRef' => 'OFF-7K2-0042',
    ]))->assertSuccessful();
    // A second sale that must not match, so a passing search is really a search.
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'offlineRef' => 'OFF-7K2-0099',
    ]))->assertSuccessful();

    // Somebody at the counter reading the reference off a printed receipt.
    $response = $this->getJson($this->world->url('/api/v1/sale?search=OFF-7K2-0042'))->assertSuccessful();

    expect($response->json('data.data'))->toHaveCount(1)
        ->and($response->json('data.data.0.reference_no'))->toBe('OFF-7K2-0042');
});

it('exposes the reference on the list so a search hit is recognisable', function (): void {
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'offlineRef' => 'OFF-7K2-0042',
    ]))->assertSuccessful();

    $row = $this->getJson($this->world->url('/api/v1/sale'))->assertSuccessful()->json('data.data.0');

    // Both halves matter: the reference the customer quoted, and the client_uuid
    // that tells the app this reference is a printed receipt number rather than
    // something the back office typed into the same field.
    expect($row['reference_no'])->toBe('OFF-7K2-0042')
        ->and($row['client_uuid'])->not->toBeNull();
});

it('keeps the reference through a replay of the same queued sale', function (): void {
    $payload = $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'offlineRef' => 'OFF-7K2-0042',
    ]);

    // The device never saw the first acknowledgement, so it posts again.
    $this->postJson($this->world->url('/api/v1/sale'), $payload)->assertSuccessful();
    $second = $this->postJson($this->world->url('/api/v1/sale'), $payload)->assertSuccessful();

    expect($second->json('data.reference_no'))->toBe('OFF-7K2-0042')
        ->and(Sale::withoutGlobalScopes()->count())->toBe(1);
});

it('leaves the reference empty for a sale that was never offline', function (): void {
    // The field belongs to the back office on any other sale, so an online sale
    // must not quietly occupy it.
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload())->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->first();
    expect($sale->reference_no)->toBeEmpty();
});

it('refuses a reference longer than the field it is stored in', function (): void {
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'offlineRef' => str_repeat('X', 41),
    ]))->assertStatus(422)->assertJsonValidationErrors('offlineRef');
});

it('records the reference under the cashier who actually served the customer', function (): void {
    // The two things a synced sale claims about its own past, together: a queue
    // drained by whoever is signed in now still files the sale under the cashier
    // who took it, and still carries the reference their till printed.
    $other = User::factory()->create([
        'name' => 'Omar',
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
    ]);

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'clientUuid' => (string) Str::uuid(),
        'offlineRef' => 'OFF-7K2-0042',
        'clientUserId' => $other->id,
    ]))->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->first();
    expect($sale->created_by)->toBe($other->id)
        ->and($sale->reference_no)->toBe('OFF-7K2-0042');
});
