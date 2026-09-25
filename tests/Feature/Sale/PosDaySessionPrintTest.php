<?php

use App\Http\Middleware\RequireOpenDaySession;
use App\Models\SaleDaySession;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The POS context strip no longer carries the day session's thermal prints —
 * those stay on the day session pages.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id, 'name' => 'sale.create', 'guard_name' => 'web',
    ]));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
    $this->withoutMiddleware(RequireOpenDaySession::class);

    $this->session = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => now()->subHour(),
        'status' => 'open',
    ]);
});

it('does not hand the POS the day session print links', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id, 'name' => 'day session.print', 'guard_name' => 'web',
    ]));

    $this->get($this->world->url(route('sale::pos', absolute: false)))->assertInertia(fn ($page) => $page
        ->missing('canPrintDaySession')
        ->where('daySession.id', $this->session->id)
        ->missing('daySession.print_url')
        ->missing('daySession.print_combined_url'));
});
