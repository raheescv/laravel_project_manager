<?php

use App\Livewire\Sale\Table;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The Sales list has to stay fast for a tenant with a million sales as much as
 * for one with a thousand: a fixed number of queries per render, one aggregate
 * pass for totals + count, and every query bounded to the tenant's own rows.
 */
beforeEach(function (): void {
    // The other tenant is built first so the list's own world is the current tenant.
    $this->otherWorld = PosWorld::create();
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function insertPerformanceSale(PosWorld $world, string $invoiceNo, array $overrides = []): int
{
    return DB::table('sales')->insertGetId(array_merge([
        'tenant_id' => $world->tenant->id,
        'invoice_no' => $invoiceNo,
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => now()->toDateString(),
        'status' => 'completed',
        'gross_amount' => 100,
        'paid' => 60,
        'created_by' => $world->user->id,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ], $overrides));
}

function countSaleTableRenderQueries(): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    Livewire::test(Table::class)->set('status', '');
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

it('keeps the query count flat as rows from more creators are added', function (): void {
    $addSalesFromNewCreators = function (int $count, string $prefix): void {
        foreach (range(1, $count) as $n) {
            $creator = User::factory()->create(['tenant_id' => $this->world->tenant->id]);
            insertPerformanceSale($this->world, "{$prefix}{$n}", ['created_by' => $creator->id]);
        }
    };

    $addSalesFromNewCreators(3, 'INVFEW');
    // Warm the per-tenant settings cache so both measurements see the same state.
    countSaleTableRenderQueries();
    $fewRowsQueries = countSaleTableRenderQueries();

    $addSalesFromNewCreators(10, 'INVMANY');
    $manyRowsQueries = countSaleTableRenderQueries();

    expect($manyRowsQueries)->toBe($fewRowsQueries);
});

it('totals and counts only the current tenant\'s rows', function (): void {
    insertPerformanceSale($this->world, 'INVMINE1');
    insertPerformanceSale($this->world, 'INVMINE2');
    // Same branch id, other tenant: only the tenant scope keeps this out.
    insertPerformanceSale($this->otherWorld, 'INVTHEIRS', [
        'branch_id' => $this->world->branch->id,
        'gross_amount' => 999,
    ]);

    $component = Livewire::test(Table::class)->set('status', '');

    expect($component->get('totals'))
        ->toMatchArray(['total_count' => 2, 'gross_amount' => 200.0, 'grand_total' => 200.0, 'paid' => 120.0, 'balance' => 80.0])
        ->and($component->viewData('data')->total())->toBe(2);
    $component->assertSee('INVMINE1')->assertDontSee('INVTHEIRS');
});

it('includes both edges of the date range on the invoice date', function (): void {
    insertPerformanceSale($this->world, 'INVBEFORE', ['date' => '2026-03-09']);
    insertPerformanceSale($this->world, 'INVFROMEDGE', ['date' => '2026-03-10']);
    insertPerformanceSale($this->world, 'INVTOEDGE', ['date' => '2026-03-12']);
    insertPerformanceSale($this->world, 'INVAFTER', ['date' => '2026-03-13']);

    $component = Livewire::test(Table::class)
        ->set('status', '')
        ->set('from_date', '2026-03-10')
        ->set('to_date', '2026-03-12');

    expect($component->get('totals')['total_count'])->toBe(2);
    $component->assertSee('INVFROMEDGE')->assertSee('INVTOEDGE')
        ->assertDontSee('INVBEFORE')->assertDontSee('INVAFTER');
});

it('includes both edges of the date range on the created-at stamp', function (): void {
    insertPerformanceSale($this->world, 'INVBEFORE', ['created_at' => '2026-03-09 23:59:59']);
    insertPerformanceSale($this->world, 'INVFROMEDGE', ['created_at' => '2026-03-10 00:00:00']);
    insertPerformanceSale($this->world, 'INVTOEDGE', ['created_at' => '2026-03-12 23:59:59']);
    insertPerformanceSale($this->world, 'INVAFTER', ['created_at' => '2026-03-13 00:00:00']);

    $component = Livewire::test(Table::class)
        ->set('status', '')
        ->set('based_on', 'created_at')
        ->set('from_date', '2026-03-10')
        ->set('to_date', '2026-03-12');

    expect($component->get('totals')['total_count'])->toBe(2);
    $component->assertSee('INVFROMEDGE')->assertSee('INVTOEDGE')
        ->assertDontSee('INVBEFORE')->assertDontSee('INVAFTER');
});

it('recomputes totals only when the filters change', function (): void {
    insertPerformanceSale($this->world, 'INVCACHED');

    $component = Livewire::test(Table::class)->set('status', '');
    $key = $component->get('totalsKey');

    $component->call('sortBy', 'date')->call('toggleRange', ['1'], true);
    expect($component->get('totalsKey'))->toBe($key);

    $component->set('search', 'nothing-matches');
    expect($component->get('totalsKey'))->not->toBe($key)
        ->and($component->get('totals')['total_count'])->toBe(0);
});

it('falls back to sorting by id for an unknown column', function (): void {
    insertPerformanceSale($this->world, 'INVSORT');

    Livewire::test(Table::class)
        ->call('sortBy', 'malicious_column; DROP TABLE sales')
        ->assertSet('sortField', 'sales.id')
        ->assertOk();
});

it('matches a numeric search on the exact sale id', function (): void {
    $id = insertPerformanceSale($this->world, 'INVEXACT');
    // Its id contains the searched id as a substring, so a LIKE would match it.
    insertPerformanceSale($this->world, 'INVNEAR', ['id' => (int) ($id.'1')]);

    $component = Livewire::test(Table::class)
        ->set('status', '')
        ->set('search', (string) $id);

    expect($component->get('totals')['total_count'])->toBe(1);
    $component->assertSee('INVEXACT')->assertDontSee('INVNEAR');
});
