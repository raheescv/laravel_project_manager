<?php

use App\Models\PhysicalVisitor;
use Tests\Support\PosWorld;

/**
 * /visitors/stats returns JSON counts and is not swallowed by /visitors/{visitor}.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

function physicalVisitor(string $status): PhysicalVisitor
{
    return PhysicalVisitor::create([
        'branch_id' => test()->world->branch->id,
        'name' => 'Visitor '.uniqid(),
        'id_card_number' => uniqid(),
        'purpose_of_visit' => 'Meeting',
        'check_in_time' => now(),
        'status' => $status,
    ]);
}

it('returns visitor stats as json', function (): void {
    physicalVisitor('checked_in');
    physicalVisitor('checked_in');
    physicalVisitor('checked_out');

    $this->get(route('visitors.stats'))
        ->assertOk()
        ->assertExactJson([
            'total_visitors' => 3,
            'currently_checked_in' => 2,
            'checked_out' => 1,
        ]);
});
