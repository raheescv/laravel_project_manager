<?php

use App\Models\EmployeeCommission;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Reports ▸ Breakdown ▸ Commission on the mobile app: one line per staff
 * member × product, net of returns, at the commission % configured for that
 * pair. An admin reads every line (and can narrow it to one person); a
 * rank-and-file employee reads only their own.
 *
 * @see App\Actions\V1\Report\CommissionAction
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    $this->employee = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
        'type' => 'employee',
        'is_admin' => 0,
    ]);

    foreach ([$this->world->user, $this->employee] as $user) {
        foreach (['report.sale item', 'report.sales overview'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
    }

    // Admin: 2 × QAR 50 at 5% = QAR 5. Employee: 1 × QAR 50 at 20% = QAR 10.
    // So commission ranks the employee first and net sales ranks the admin first.
    EmployeeCommission::create([
        'employee_id' => $this->world->user->id,
        'product_id' => $this->world->product->id,
        'commission_percentage' => 5,
    ]);
    EmployeeCommission::create([
        'employee_id' => $this->employee->id,
        'product_id' => $this->world->product->id,
        'commission_percentage' => 20,
    ]);

    $post = function (User $user, float $quantity): void {
        Sanctum::actingAs($user);
        $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
            'clientUuid' => (string) Str::uuid(),
            'items' => [[
                'productId' => $this->world->product->id,
                'employeeId' => $user->id,
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
        $this->world->url('/api/v1/admin/reports?'.http_build_query(['type' => 'commission'] + $query))
    );
});

it('lists every staff × product line for an admin, ranked by commission', function (): void {
    Sanctum::actingAs($this->world->user);

    $data = ($this->report)([])->assertSuccessful()->json('data');

    expect($data['rows'])->toHaveCount(2)
        ->and($data['rows'][0])->toMatchArray([
            'employee_id' => (string) $this->employee->id,
            'product_id' => (string) $this->world->product->id,
            'employee_name' => $this->employee->name,
            'quantity' => 1,
            'net_amount' => 50,
            'commission_percentage' => 20,
            'commission' => 10,
        ])
        ->and($data['rows'][1]['employee_id'])->toBe((string) $this->world->user->id)
        ->and($data['rows'][1]['commission'])->toEqual(5.0)
        ->and($data['summary'])->toMatchArray([
            'rows' => 2,
            'employees' => 2,
            'total_quantity' => 3,
            'total_net_amount' => 150,
            'total_commission' => 15,
        ]);
});

it('re-ranks by net sales and flips the direction on request', function (): void {
    Sanctum::actingAs($this->world->user);

    $byNet = ($this->report)(['sort' => 'net'])->assertSuccessful()->json('data.rows');
    expect(array_column($byNet, 'employee_id'))
        ->toBe([(string) $this->world->user->id, (string) $this->employee->id]);

    $lowestFirst = ($this->report)(['sort' => 'amount', 'direction' => 'asc'])->assertSuccessful()->json('data.rows');
    expect(array_column($lowestFirst, 'commission'))->toEqual([5.0, 10.0]);
});

it('narrows an admin to one staff member', function (): void {
    Sanctum::actingAs($this->world->user);

    $data = ($this->report)(['employee_id' => $this->world->user->id])->assertSuccessful()->json('data');

    expect($data['rows'])->toHaveCount(1)
        ->and($data['rows'][0]['employee_id'])->toBe((string) $this->world->user->id)
        ->and($data['summary']['employees'])->toBe(1)
        ->and($data['summary']['total_commission'])->toEqual(5.0);
});

it('shows a rank-and-file employee their own commission only', function (): void {
    Sanctum::actingAs($this->employee);

    // Asking for a colleague changes nothing: the scope follows who is asking.
    $data = ($this->report)(['employee_id' => $this->world->user->id])->assertSuccessful()->json('data');

    expect($data['rows'])->toHaveCount(1)
        ->and($data['rows'][0]['employee_id'])->toBe((string) $this->employee->id)
        ->and($data['summary']['total_commission'])->toEqual(10.0)
        ->and($data['filters']['employee_id'])->toBe((string) $this->employee->id);
});

it('pages the lines while the summary keeps full-set totals', function (): void {
    Sanctum::actingAs($this->world->user);

    $data = ($this->report)(['per_page' => 1, 'page' => 2])->assertSuccessful()->json('data');

    expect($data['rows'])->toHaveCount(1)
        ->and($data['rows'][0]['employee_id'])->toBe((string) $this->world->user->id)
        ->and($data['pagination'])->toMatchArray(['current_page' => 2, 'last_page' => 2, 'total' => 2])
        ->and($data['summary'])->toMatchArray([
            'rows' => 2,
            'employees' => 2,
            'total_quantity' => 3,
            'total_commission' => 15,
        ]);
});

it('includes both ends of the date range and drops sales outside it', function (): void {
    Sanctum::actingAs($this->world->user);

    $saleDate = Carbon::parse(Sale::query()->value('date'));

    $sameDay = ($this->report)([
        'startDate' => $saleDate->toDateString(),
        'endDate' => $saleDate->toDateString(),
    ])->assertSuccessful()->json('data');

    $dayBefore = ($this->report)([
        'startDate' => $saleDate->copy()->subDays(7)->toDateString(),
        'endDate' => $saleDate->copy()->subDay()->toDateString(),
    ])->assertSuccessful()->json('data');

    expect($sameDay['summary']['rows'])->toBe(2)
        ->and($sameDay['summary']['total_commission'])->toEqual(15.0)
        ->and($dayBefore['rows'])->toBe([])
        ->and($dayBefore['summary']['total_commission'])->toEqual(0.0);
});
