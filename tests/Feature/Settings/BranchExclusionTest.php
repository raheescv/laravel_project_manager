<?php

use App\Actions\Settings\Branch\CreateAction;
use App\Actions\Settings\Branch\UpdateAction;
use App\Models\Branch;
use Illuminate\Support\Facades\Queue;
use Tests\Support\PosWorld;

/**
 * The "Exclude from Showcase" toggle on Settings → Branch has to survive the
 * round trip through the create/update actions — a flag the form writes but the
 * action drops would silently keep a warehouse on the public shop list.
 */
beforeEach(function (): void {
    Queue::fake();
    $this->world = PosWorld::create();
});

it('stores the exclusion when a branch is created with it on', function (): void {
    $response = (new CreateAction)->execute([
        'name' => 'Central Warehouse',
        'code' => 'WH',
        'location' => 'Industrial Area',
        'exclude_from_showcase' => true,
    ]);

    expect($response['success'])->toBeTrue()
        ->and(Branch::find($response['data']->id)->exclude_from_showcase)->toBeTrue();
});

it('defaults a new branch to being listed', function (): void {
    $response = (new CreateAction)->execute([
        'name' => 'Galleria Mall',
        'code' => 'GM',
    ]);

    expect(Branch::find($response['data']->id)->exclude_from_showcase)->toBeFalse();
});

it('toggles the exclusion back off on update', function (): void {
    $branch = Branch::create([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'Doha Mall',
        'code' => 'DM',
        'exclude_from_showcase' => true,
    ]);

    $response = (new UpdateAction)->execute([
        'name' => 'Doha Mall',
        'code' => 'DM',
        'exclude_from_showcase' => false,
    ], $branch->id);

    expect($response['success'])->toBeTrue()
        ->and($branch->fresh()->exclude_from_showcase)->toBeFalse();
});
