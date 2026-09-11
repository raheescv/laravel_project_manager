<?php

use App\Console\Commands\OpenSaleDaySessionsCommand;
use App\Models\Branch;
use App\Models\Configuration;
use App\Models\Holiday;
use App\Models\SaleDaySession;
use App\Models\Tenant;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Tests\Support\PosWorld;

/**
 * sale-day-sessions:open-daily is the opening half of auto close: with the
 * switch on, every branch's day session opens itself at the Settings -> Working
 * Day opening time. It runs every minute, so most of what is tested here is the
 * many minutes on which it must do nothing.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->second = $this->world->addBranch();

    // Monday 14 Sep 2026 - a working day in the week seeded below.
    $this->monday = Carbon::parse('2026-09-14');

    aoSeedWeek($this->world->tenant, [1, 2, 3, 4, 5], '09:00', '18:00');
    aoSwitch($this->world->tenant, 'yes');
});

afterEach(fn () => Carbon::setTestNow());

function aoSeedWeek(Tenant $tenant, array $workingDays, string $start, string $end): void
{
    foreach (array_keys(WorkingDay::DAY_INDEX) as $index => $name) {
        WorkingDay::create([
            'tenant_id' => $tenant->id, 'day_name' => ucfirst($name),
            'is_working' => in_array($index, $workingDays, true),
            'start_time' => $start, 'end_time' => $end, 'order_no' => $index,
        ]);
    }
}

function aoSwitch(Tenant $tenant, string $value): void
{
    Configuration::withoutTenant()->updateOrCreate(
        ['tenant_id' => $tenant->id, 'key' => OpenSaleDaySessionsCommand::SETTING_KEY],
        ['value' => $value],
    );
}

/** Every session on the tenant, regardless of which tenant is pinned after the run. */
function aoSessions(Tenant $tenant)
{
    return SaleDaySession::withoutGlobalScopes()->where('tenant_id', $tenant->id)->orderBy('branch_id')->get();
}

/** A second tenant with one branch, for the cross-tenant tests. */
function aoOtherTenant(): array
{
    $tenant = Tenant::factory()->create();
    $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'Other Branch', 'code' => 'OB']);

    return [$tenant, $branch];
}

it('opens a session for every branch once the opening time has passed, stamped with that time', function (): void {
    Carbon::setTestNow($this->monday->copy()->setTime(9, 3));

    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    $sessions = aoSessions($this->world->tenant);

    expect($sessions)->toHaveCount(2)
        ->and($sessions->pluck('branch_id')->all())->toBe([$this->world->branch->id, $this->second->id]);

    foreach ($sessions as $session) {
        expect($session->status)->toBe('open')
            ->and($session->opened_at->format('Y-m-d H:i:s'))->toBe('2026-09-14 09:00:00')
            ->and($session->opened_by)->toBeNull()
            ->and((float) $session->opening_amount)->toBe(0.0);
    }
});

it('waits for the opening time', function (): void {
    Carbon::setTestNow($this->monday->copy()->setTime(8, 59));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    expect(aoSessions($this->world->tenant))->toHaveCount(0);

    Carbon::setTestNow($this->monday->copy()->setTime(9, 0));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    expect(aoSessions($this->world->tenant))->toHaveCount(2);
});

it('runs every minute without opening a second session', function (): void {
    Carbon::setTestNow($this->monday->copy()->setTime(9, 3));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    Carbon::setTestNow($this->monday->copy()->setTime(15, 30));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    expect(aoSessions($this->world->tenant))->toHaveCount(2);
});

it('never reopens a session that was closed today', function (): void {
    SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => $this->monday->copy()->setTime(9, 0),
        'closed_at' => $this->monday->copy()->setTime(13, 0),
        'closed_by' => $this->world->user->id,
        'status' => 'closed',
    ]);

    Carbon::setTestNow($this->monday->copy()->setTime(14, 0));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    $sessions = aoSessions($this->world->tenant);

    expect($sessions)->toHaveCount(2)
        ->and($sessions->where('branch_id', $this->world->branch->id)->first()->status)->toBe('closed')
        ->and($sessions->where('branch_id', $this->second->id)->first()->status)->toBe('open');
});

it('leaves a branch alone while an earlier day is still open', function (): void {
    $sunday = SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => $this->monday->copy()->subDay()->setTime(9, 0),
        'status' => 'open',
    ]);

    Carbon::setTestNow($this->monday->copy()->setTime(9, 5));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    $sessions = aoSessions($this->world->tenant);

    expect($sessions)->toHaveCount(2)
        ->and($sessions->where('branch_id', $this->world->branch->id)->first()->id)->toBe($sunday->id)
        ->and($sessions->where('branch_id', $this->second->id)->first()->opened_at->toDateString())->toBe('2026-09-14');
});

it('stays shut on a day the working week switches off', function (): void {
    // Saturday is not in [1..5].
    Carbon::setTestNow($this->monday->copy()->subDays(2)->setTime(9, 5));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    expect(aoSessions($this->world->tenant))->toHaveCount(0);
});

it('stays shut on a holiday', function (): void {
    Holiday::create([
        'tenant_id' => $this->world->tenant->id, 'name' => 'National Day', 'date' => $this->monday->toDateString(),
    ]);

    Carbon::setTestNow($this->monday->copy()->setTime(9, 5));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    expect(aoSessions($this->world->tenant))->toHaveCount(0);
});

it('is off until the tenant switches it on', function (): void {
    aoSwitch($this->world->tenant, 'no');

    Carbon::setTestNow($this->monday->copy()->setTime(9, 5));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    expect(aoSessions($this->world->tenant))->toHaveCount(0);
});

it('only opens for tenants that switched it on, and on each one\'s own working week', function (): void {
    [$other, $otherBranch] = aoOtherTenant();
    aoSeedWeek($other, [1, 2, 3, 4, 5], '11:00', '20:00');

    // Switched off on the other tenant: nothing, however late it gets.
    Carbon::setTestNow($this->monday->copy()->setTime(12, 0));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    expect(aoSessions($this->world->tenant))->toHaveCount(2)
        ->and(aoSessions($other))->toHaveCount(0);

    // Switched on: it follows ITS week (11:00), not the first tenant's (09:00).
    aoSwitch($other, 'yes');

    Carbon::setTestNow($this->monday->copy()->addDay()->setTime(10, 0));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();
    expect(aoSessions($other))->toHaveCount(0);

    Carbon::setTestNow($this->monday->copy()->addDay()->setTime(11, 0));
    $this->artisan('sale-day-sessions:open-daily')->assertSuccessful();

    $session = aoSessions($other)->first();

    expect(aoSessions($other))->toHaveCount(1)
        ->and($session->branch_id)->toBe($otherBranch->id)
        ->and($session->opened_at->format('Y-m-d H:i'))->toBe('2026-09-15 11:00');
});
