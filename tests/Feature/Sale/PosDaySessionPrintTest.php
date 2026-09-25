<?php

use App\Http\Middleware\RequireOpenDaySession;
use App\Models\SaleDaySession;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The POS context strip carries the open day session's two thermal prints —
 * the detailed "Sale Bill Report" and its combined variant — for staff with
 * `day session.print`.
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

it('hands the POS both day session print links', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id, 'name' => 'day session.print', 'guard_name' => 'web',
    ]));

    $this->get($this->world->url(route('sale::pos', absolute: false)))->assertInertia(fn ($page) => $page
        ->where('canPrintDaySession', true)
        ->where('daySession.print_url', route('print::sale::day-session-report', $this->session->id))
        ->where('daySession.print_combined_url', route('print::sale::day-session-report-combined', $this->session->id)));
});

it('hides the day session prints without the permission', function (): void {
    $this->get($this->world->url(route('sale::pos', absolute: false)))->assertInertia(fn ($page) => $page->where('canPrintDaySession', false));
});
