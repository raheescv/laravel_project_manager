<?php

use App\Models\Branch;
use Tests\Support\PosWorld;

/**
 * GET /api/v1/branches is the public shop list the showcase app and website
 * render — every branch on it is somewhere a customer can be sent. Back-office
 * branches (warehouse, head office, an online-only ledger) are flagged
 * "Exclude from Showcase" in Settings → Branch and must never appear there.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
});

function branchList(PosWorld $world, array $query = []): array
{
    $response = test()->getJson($world->url('/api/v1/branches?'.http_build_query($query)));
    $response->assertSuccessful();

    return $response->json('data');
}

it('leaves an excluded branch out of the public listing', function (): void {
    Branch::create([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'Central Warehouse',
        'code' => 'WH',
        'location' => 'Industrial Area',
        'exclude_from_showcase' => true,
    ]);

    $codes = collect(branchList($this->world))->pluck('code')->all();

    expect($codes)->toContain('MB')
        ->and($codes)->not->toContain('WH');
});

it('still lists a branch that is not excluded', function (): void {
    Branch::create([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'Galleria Mall',
        'code' => 'GM',
        'location' => 'Galleria Mall',
    ]);

    expect(collect(branchList($this->world))->pluck('code')->all())
        ->toContain('GM');
});

it('returns the hidden branch when a caller opts in', function (): void {
    // The same endpoint backs internal pickers (the technician app's store
    // list), so there has to be a way to ask for everything.
    Branch::create([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'Central Warehouse',
        'code' => 'WH',
        'exclude_from_showcase' => true,
    ]);

    expect(collect(branchList($this->world, ['include_hidden' => 1]))->pluck('code')->all())
        ->toContain('WH');
});

it('keeps the exclusion out of the way of the search filter', function (): void {
    Branch::create([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'Doha Mall',
        'code' => 'DM',
        'location' => 'Doha Mall',
        'exclude_from_showcase' => true,
    ]);

    expect(branchList($this->world, ['query' => 'Doha']))->toBeEmpty();
});
