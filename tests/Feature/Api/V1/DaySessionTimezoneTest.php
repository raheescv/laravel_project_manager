<?php

use App\Models\SaleDaySession;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Closing the day must not depend on the phone and the server agreeing on a
 * timezone.
 *
 * The app stamps the moment from the *device* clock. A till in Qatar talking to
 * a server on IST (or the reverse) sends a wall clock that is hours away from
 * the server's own, and the "not in the future" guard reads that as tomorrow —
 * the day can never be closed. The app now sends an absolute instant and the
 * request converts it into app time before anything is compared.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    // `permissions` carries a tenant_id, so the row has to be built with one
    // rather than through Spatie's findOrCreate (see PermissionSeeder).
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id,
        'name' => 'day session.create',
        'guard_name' => 'web',
    ]));
    Sanctum::actingAs($this->world->user);

    $this->session = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => now()->subHours(6),
        'status' => 'open',
    ]);

    $this->close = fn (string $date) => $this->postJson(
        $this->world->url('/api/v1/admin/day-status'),
        ['date' => $date]
    );
});

it('closes the day for a phone whose timezone is ahead of the server', function (): void {
    // Kiritimati (+14) is the furthest ahead any real device can be — its wall
    // clock is a full day off in the direction the old guard rejected.
    ($this->close)(Carbon::now('Pacific/Kiritimati')->utc()->toIso8601String())
        ->assertSuccessful();

    $session = $this->session->fresh();

    // Seconds of tolerance, not an exact stamp: the request can straddle the
    // tick between the close and this assertion. The point is that the stored
    // moment is the server's now, not a clock 14 hours away.
    expect($session->status)->toBe('closed')
        ->and(abs($session->closed_at->diffInSeconds(now())))->toBeLessThan(10);
});

it('closes the day for a phone whose timezone is behind the server', function (): void {
    // Behind the server, the close would land before the opening moment.
    ($this->close)(Carbon::now('Pacific/Midway')->utc()->toIso8601String())
        ->assertSuccessful();

    expect($this->session->fresh()->status)->toBe('closed');
});

it('records the close in app time, so it lines up with the sales it covers', function (): void {
    // Sales are stamped with the server's now(). If the session window were kept
    // in device time the day report would cover the wrong hours.
    ($this->close)(Carbon::now('Asia/Qatar')->utc()->toIso8601String())->assertSuccessful();

    $closedAt = $this->session->fresh()->closed_at;

    expect(abs($closedAt->diffInSeconds(now())))->toBeLessThan(10)
        ->and($closedAt->greaterThanOrEqualTo($this->session->opened_at))->toBeTrue();
});

it('still accepts a bare wall clock from an older build', function (): void {
    // Pre-fix installs send `Y-m-d H:i:s` with no offset; it has always meant
    // app time and must keep meaning that.
    ($this->close)(now()->format('Y-m-d H:i:s'))->assertSuccessful();

    expect($this->session->fresh()->status)->toBe('closed');
});

it('still refuses a moment that is genuinely in the future', function (): void {
    // The guard is relaxed about timezones, not about backdating tomorrow.
    ($this->close)(now()->addDay()->toIso8601String())
        ->assertStatus(422)
        ->assertJsonPath('errors.date.0', 'The date & time cannot be in the future.');

    expect($this->session->fresh()->status)->toBe('open');
});

it('still refuses a close that lands before the opening', function (): void {
    ($this->close)(now()->subHours(9)->toIso8601String())
        ->assertStatus(422);

    expect($this->session->fresh()->status)->toBe('open');
});

it('rejects an unparseable date instead of crashing', function (): void {
    ($this->close)('not-a-date')->assertStatus(422);

    expect($this->session->fresh()->status)->toBe('open');
});
