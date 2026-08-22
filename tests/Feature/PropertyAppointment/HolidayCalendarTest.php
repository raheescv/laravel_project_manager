<?php

use App\Actions\Settings\Holiday\CreateAction;
use App\Livewire\Settings\Holiday as HolidaySettings;
use App\Models\Holiday;
use App\Models\PropertyAppointmentAvailability;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkingDay;
use App\Services\PropertyAppointment\SlotService;
use Carbon\Carbon;
use Livewire\Livewire;

/**
 * The holiday calendar is the company's list of closed DATES, sitting on top of
 * the weekly pattern in Settings -> Working Day. Its whole job is subtractive:
 * a date on this list must offer nothing, to anybody, however open the week and
 * the employee's own hours say that weekday is.
 */

/** A tenant with an employee bookable every day, 09:00–13:00. */
function hcSeed(): array
{
    // `code` is unique and blank by default, so a test that seeds two tenants
    // collides on the second one unless each is given its own.
    $suffix = uniqid();
    $tenant = Tenant::create([
        'name' => 'HC Tenant', 'subdomain' => 'hc'.$suffix, 'code' => 'hc'.$suffix, 'is_active' => 1,
    ]);
    app(App\Services\TenantService::class)->setCurrentTenant($tenant);
    session(['tenant_id' => $tenant->id]);

    $employee = User::create([
        'tenant_id' => $tenant->id, 'name' => 'HC Employee', 'type' => 'employee',
        'email' => 'hc'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);

    foreach (range(0, 6) as $dayOfWeek) {
        PropertyAppointmentAvailability::create([
            'tenant_id' => $tenant->id, 'user_id' => $employee->id, 'day_of_week' => $dayOfWeek,
            'start_time' => '09:00', 'end_time' => '13:00', 'is_active' => true,
        ]);
    }

    return compact('tenant', 'employee');
}

/** Grant one ability, creating the permission row if it does not exist yet. */
function hcGrant(User $user, string $ability): void
{
    $user->givePermissionTo(
        Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => $ability, 'guard_name' => 'web', 'tenant_id' => $user->tenant_id]
        )
    );
}

/** A date far enough ahead to clear the minimum-notice rule. */
function hcDate(int $daysAhead = 5): Carbon
{
    return now()->addDays($daysAhead)->startOfDay();
}

it('offers no slots on a company holiday', function () {
    $seed = hcSeed();
    $date = hcDate();

    $before = app(SlotService::class)->availableSlots($seed['employee']->id);
    expect($before)->toHaveKey($date->toDateString());

    Holiday::create([
        'tenant_id' => $seed['tenant']->id, 'name' => 'National Day',
        'date' => $date->toDateString(),
    ]);

    $after = app(SlotService::class)->availableSlots($seed['employee']->id);

    expect($after)->not->toHaveKey($date->toDateString())
        // Only that one day goes — the rest of the window is untouched.
        ->and(count($after))->toBe(count($before) - 1);
});

it('closes a holiday even when the employee keeps their own hours that day', function () {
    $seed = hcSeed();
    $date = hcDate();

    // The employee's personal availability is the most specific rule there is;
    // a company closure still wins, because it is a closure and not an absence.
    Holiday::create([
        'tenant_id' => $seed['tenant']->id, 'name' => 'Eid', 'date' => $date->toDateString(),
    ]);

    expect(app(SlotService::class)->openWindows($seed['employee']->id))
        ->not->toHaveKey($date->toDateString());
});

it('leaves the day open once the holiday is switched off', function () {
    $seed = hcSeed();
    $date = hcDate();

    $holiday = Holiday::create([
        'tenant_id' => $seed['tenant']->id, 'name' => 'Company Day',
        'date' => $date->toDateString(), 'is_active' => false,
    ]);

    expect(app(SlotService::class)->availableSlots($seed['employee']->id))
        ->toHaveKey($date->toDateString());

    $holiday->update(['is_active' => true]);

    expect(app(SlotService::class)->availableSlots($seed['employee']->id))
        ->not->toHaveKey($date->toDateString());
});

it('repeats an annual holiday in years it was never entered for', function () {
    $seed = hcSeed();
    $date = hcDate();

    // Entered years ago, still closing the same month and day today.
    Holiday::create([
        'tenant_id' => $seed['tenant']->id, 'name' => 'Founders Day',
        'date' => $date->copy()->subYears(3)->toDateString(), 'is_recurring' => true,
    ]);

    expect(app(SlotService::class)->availableSlots($seed['employee']->id))
        ->not->toHaveKey($date->toDateString());
});

it('does not repeat a one-off holiday into later years', function () {
    $seed = hcSeed();
    $date = hcDate();

    Holiday::create([
        'tenant_id' => $seed['tenant']->id, 'name' => 'One-off closure',
        'date' => $date->copy()->subYears(3)->toDateString(), 'is_recurring' => false,
    ]);

    expect(app(SlotService::class)->availableSlots($seed['employee']->id))
        ->toHaveKey($date->toDateString());
});

it('keeps a 29 February holiday inside February in a common year', function () {
    $seed = hcSeed();

    $holiday = Holiday::create([
        'tenant_id' => $seed['tenant']->id, 'name' => 'Leap Day',
        'date' => '2024-02-29', 'is_recurring' => true,
    ]);

    // Carbon would roll this to 1 March, which closes the wrong day.
    expect($holiday->occurrenceIn(2027)->toDateString())->toBe('2027-02-28')
        ->and($holiday->occurrenceIn(2028)->toDateString())->toBe('2028-02-29');
});

it('refuses a typed booking on a holiday and names it', function () {
    $seed = hcSeed();
    $date = hcDate();

    Holiday::create([
        'tenant_id' => $seed['tenant']->id, 'name' => 'National Day',
        'date' => $date->toDateString(),
    ]);

    $problem = app(SlotService::class)->windowProblem(
        $seed['employee']->id,
        $date->copy()->setTime(10, 0),
        $date->copy()->setTime(11, 0),
    );

    expect($problem['ok'])->toBeFalse()
        ->and($problem['reason'])->toContain('National Day');
});

it('shuts a working day that the week has switched on', function () {
    $seed = hcSeed();
    $date = hcDate();

    foreach (array_keys(WorkingDay::DAY_INDEX) as $index => $name) {
        WorkingDay::create([
            'tenant_id' => $seed['tenant']->id, 'day_name' => ucfirst($name),
            'is_working' => true, 'start_time' => '09:00', 'end_time' => '18:00',
            'order_no' => $index,
        ]);
    }

    Holiday::create([
        'tenant_id' => $seed['tenant']->id, 'name' => 'Shutdown', 'date' => $date->toDateString(),
    ]);

    expect(app(SlotService::class)->availableSlots($seed['employee']->id))
        ->not->toHaveKey($date->toDateString());
});

it('names every closed date in a range, one entry per date', function () {
    $seed = hcSeed();

    Holiday::create(['tenant_id' => $seed['tenant']->id, 'name' => 'Day One', 'date' => '2026-12-18']);
    Holiday::create(['tenant_id' => $seed['tenant']->id, 'name' => 'Day Two', 'date' => '2026-12-19']);

    $dates = Holiday::datesBetween(Carbon::parse('2026-12-01'), Carbon::parse('2026-12-31'));

    expect($dates)->toBe(['2026-12-18' => 'Day One', '2026-12-19' => 'Day Two']);
});

it('adds a holiday from the settings screen', function () {
    $seed = hcSeed();
    hcGrant($seed['employee'], 'configuration.settings');
    $this->actingAs($seed['employee']);

    Livewire::test(HolidaySettings::class)
        ->set('name', 'National Day')
        ->set('date', '2026-12-18')
        ->set('is_recurring', true)
        ->call('save')
        ->assertDispatched('success')
        // The list follows the holiday to the year it was filed under.
        ->assertSet('year', 2026)
        ->assertSet('name', '');

    expect(Holiday::where('name', 'National Day')->exists())->toBeTrue();
});

it('will not save a holiday with no name', function () {
    $seed = hcSeed();

    $response = (new CreateAction())->execute(['name' => '', 'date' => '2026-12-18'], $seed['employee']->id);

    expect($response['success'])->toBeFalse()
        ->and(Holiday::count())->toBe(0);
});

it('keeps one tenant\'s holidays out of another\'s calendar', function () {
    $first = hcSeed();
    Holiday::create(['tenant_id' => $first['tenant']->id, 'name' => 'Theirs', 'date' => hcDate()->toDateString()]);

    $second = hcSeed();

    expect(app(SlotService::class)->availableSlots($second['employee']->id))
        ->toHaveKey(hcDate()->toDateString());
});
