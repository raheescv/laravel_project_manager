<?php

use App\Models\SaleDaySession;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The dashboard's day pill is refreshed from `GET /admin/day-status`, not from
 * the copy of the day session the phone cached at sign-in. So the endpoint has
 * to answer with everything that pill draws — the status, the business date,
 * the opening moment and, once the day is shut, the last close — and it has to
 * answer for a cashier too, since a shared till is exactly where another device
 * moves the day underneath this one.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Sanctum::actingAs($this->world->user);

    $this->grantDaySession = fn () => $this->world->user->givePermissionTo(
        Permission::firstOrCreate([
            'tenant_id' => $this->world->tenant->id,
            'name' => 'day session.create',
            'guard_name' => 'web',
        ])
    );

    $this->status = fn () => $this->getJson($this->world->url('/api/v1/admin/day-status'));
});

it('reports an open day with the business date and opening moment', function (): void {
    $session = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => now()->subHours(6),
        'status' => 'open',
    ]);

    ($this->status)()
        ->assertSuccessful()
        ->assertJsonPath('data.is_open', true)
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.date', $session->opened_at->format('Y-m-d'))
        ->assertJsonPath('data.opened_at', $session->opened_at->format('Y-m-d H:i:s'))
        ->assertJsonPath('data.last_closed_at', null);
});

it('reports the last close once the day is shut', function (): void {
    $closed = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => now()->subHours(9),
        'closed_by' => $this->world->user->id,
        'closed_at' => now()->subHours(2),
        'status' => 'closed',
    ]);

    ($this->status)()
        ->assertSuccessful()
        ->assertJsonPath('data.is_open', false)
        ->assertJsonPath('data.status', 'closed')
        ->assertJsonPath('data.date', now()->format('Y-m-d'))
        ->assertJsonPath('data.opened_at', null)
        ->assertJsonPath('data.last_closed_at', $closed->closed_at->format('Y-m-d H:i:s'));
});

it('withholds the till amounts from a user who cannot run the day', function (): void {
    SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => now()->subHours(3),
        'opening_amount' => 500,
        'status' => 'open',
    ]);

    // No 'day session.create': the status still refreshes, the float does not.
    ($this->status)()
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.session', null);

    ($this->grantDaySession)();

    ($this->status)()
        ->assertSuccessful()
        // 500.0 re-encodes as a bare `500` over JSON, so compare loosely.
        ->assertJsonPath('data.session.opening_amount', fn ($v) => (float) $v === 500.0);
});
