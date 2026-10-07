<?php

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * `Sale::employeeNames()` feeds the "Served By" line on the printed receipt —
 * it must name every item's employee and assistant, once each, alphabetically.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Sanctum::actingAs($this->world->user);

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload(['status' => 'draft']))->assertSuccessful();

    $this->sale = Sale::withoutGlobalScopes()->latest('id')->first();
    $firstItem = SaleItem::withoutGlobalScopes()->where('sale_id', $this->sale->id)->firstOrFail();
    $firstItem->replicate(['base_unit_quantity', 'gross_amount', 'net_amount', 'tax_amount', 'total'])->saveQuietly();
    $this->items = SaleItem::withoutGlobalScopes()->where('sale_id', $this->sale->id)->orderBy('id')->get();
    $this->makeUser = fn (string $name): User => User::factory()->create(['name' => $name, 'tenant_id' => $this->world->tenant->id]);
});

it('includes item assistants alongside employees', function (): void {
    $jolina = ($this->makeUser)('Jolina');
    $benita = ($this->makeUser)('Benita');
    $alia = ($this->makeUser)('Alia');

    $this->items[0]->update(['employee_id' => $jolina->id, 'assistant_id' => $benita->id]);
    $this->items[1]->update(['employee_id' => $benita->id, 'assistant_id' => $alia->id]);

    expect($this->sale->employeeNames())->toBe('Alia, Benita, Jolina');
});

it('returns an empty string when no item has an employee or assistant', function (): void {
    SaleItem::withoutGlobalScopes()->where('sale_id', $this->sale->id)->update(['employee_id' => null, 'assistant_id' => null]);

    expect($this->sale->employeeNames())->toBe('');
});
