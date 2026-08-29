<?php

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * A rank-and-file employee's dashboard is their own dashboard.
 *
 * The KPI cards were already scoped (App\Actions\V1\Dashboard\GetAction), but
 * the two blocks the dashboard fills from /admin/reports — Top performers and
 * the trend sparkline — were not, so a cashier could read the shop's takings
 * and every colleague's revenue off their own home screen.
 *
 * @see App\Models\User::seesOnlyOwnRecords()
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    $this->employee = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
        'type' => 'employee',
        'is_admin' => 0,
    ]);

    // `permissions` carries a tenant_id, so the rows have to be built with one
    // rather than through Spatie's findOrCreate (see PermissionSeeder). Every
    // account here holds the permission: what separates them is the account
    // type, not the grant — a cashier needs it for the dashboard to load at all.
    $this->grantReportAccess = function (User $user): void {
        foreach (['report.sale item', 'report.sales overview'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate([
                'tenant_id' => $this->world->tenant->id,
                'name' => $name,
                'guard_name' => 'web',
            ]));
        }
    };

    ($this->grantReportAccess)($this->world->user);
    ($this->grantReportAccess)($this->employee);

    // One QAR 100 ticket rung up by the admin, one QAR 50 ticket by the employee.
    $post = function (User $user, float $quantity): void {
        Sanctum::actingAs($user);
        $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
            'clientUuid' => (string) Str::uuid(),
            'items' => [[
                'productId' => $this->world->product->id,
                'quantity' => $quantity,
                'unitPrice' => (float) $this->world->product->mrp,
                'discount' => 0,
            ]],
            'totalPayment' => $quantity * (float) $this->world->product->mrp,
        ]))->assertSuccessful();
    };

    $post($this->world->user, 2);
    $post($this->employee, 1);

    $this->report = fn (array $query) => $this->getJson(
        $this->world->url('/api/v1/admin/reports?'.http_build_query($query))
    );
});

it('lists only the bills the employee rang up', function (): void {
    Sanctum::actingAs($this->employee);

    $data = ($this->report)(['type' => 'billwise'])->assertSuccessful()->json('data');

    expect($data['rows'])->toHaveCount(1)
        ->and($data['summary']['invoices'])->toBe(1)
        ->and($data['summary']['total_paid'])->toEqual(50.0);
});

it('ranks the employee against nobody but themselves', function (): void {
    Sanctum::actingAs($this->employee);

    $data = ($this->report)(['type' => 'employeewise'])->assertSuccessful()->json('data');

    expect($data['rows'])->toHaveCount(1)
        ->and($data['rows'][0]['employee_name'])->toBe($this->employee->name)
        ->and($data['summary']['total_revenue'])->toEqual(50.0);
});

it('ignores an employee_id the client asked for on someone else', function (): void {
    Sanctum::actingAs($this->employee);

    // The app never sends this, but the token holder can: the scope has to be
    // decided by who is asking, not by what they asked for.
    $data = ($this->report)([
        'type' => 'employeewise',
        'employee_id' => $this->world->user->id,
    ])->assertSuccessful()->json('data');

    expect($data['rows'])->toHaveCount(1)
        ->and($data['rows'][0]['employee_name'])->toBe($this->employee->name);
});

it('scopes the item breakdown and the overview to the employee', function (): void {
    Sanctum::actingAs($this->employee);

    $items = ($this->report)(['type' => 'itemwise'])->assertSuccessful()->json('data');
    expect($items['summary']['total_quantity'])->toEqual(1.0);

    $overview = ($this->report)(['type' => 'overview'])->assertSuccessful()->json('data');
    expect($overview['summary']['no_of_sales'])->toBe(1)
        ->and($overview['payments']['sales_total'])->toEqual(50.0)
        ->and($overview['employees'])->toHaveCount(1);
});

it('leaves the whole branch visible to an admin', function (): void {
    Sanctum::actingAs($this->world->user);

    $bills = ($this->report)(['type' => 'billwise'])->assertSuccessful()->json('data');
    $employees = ($this->report)(['type' => 'employeewise'])->assertSuccessful()->json('data');

    expect($bills['summary']['invoices'])->toBe(2)
        ->and($bills['summary']['total_paid'])->toEqual(150.0)
        ->and($employees['rows'])->toHaveCount(2);
});

it('leaves the whole branch visible to a back-office account with no admin flag', function (): void {
    // The shape this rule exists to let through: type 'user', is_admin 0 — an
    // owner or office account nobody ever ticked "admin" on. It is not an
    // employee, so it is not self-scoped, and the leaderboard the mobile
    // dashboard draws from `employeewise` has to come back with the full board.
    $backOffice = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
        'type' => 'user',
        'is_admin' => 0,
    ]);
    ($this->grantReportAccess)($backOffice);

    expect($backOffice->seesOnlyOwnRecords())->toBeFalse();

    Sanctum::actingAs($backOffice);

    $bills = ($this->report)(['type' => 'billwise'])->assertSuccessful()->json('data');
    $employees = ($this->report)(['type' => 'employeewise'])->assertSuccessful()->json('data');
    $overview = ($this->report)(['type' => 'overview'])->assertSuccessful()->json('data');

    expect($bills['summary']['invoices'])->toBe(2)
        ->and($bills['summary']['total_paid'])->toEqual(150.0)
        ->and($employees['rows'])->toHaveCount(2)
        ->and($overview['summary']['no_of_sales'])->toBe(2);
});

it('keeps the dashboard cards on the employee too', function (): void {
    Sanctum::actingAs($this->employee);

    $data = $this->getJson($this->world->url('/api/v1/admin/dashboard'))
        ->assertSuccessful()->json('data');

    $today = collect($data['todaySummary'])->keyBy('title');

    expect($today["Today's Sales"]['value'])->toEqual(50.0)
        ->and($today["Today's Bills"]['value'])->toBe(1);
});
