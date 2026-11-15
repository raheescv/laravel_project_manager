<?php

use App\Actions\Student\GetBalanceAction;
use App\Actions\Student\PreOrder\ChargeDueAction;
use App\Actions\Student\PreOrder\SummaryAction;
use App\Models\Sale;
use App\Models\SaleDaySession;
use App\Models\StudentPreOrder;
use App\Models\StudentPreOrderCollection;
use Illuminate\Support\Carbon;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * A pre-order is paid when it is placed: a completed sale dated the day the meal
 * is served, paid from the student card. Changing or removing it before the
 * cut-off gives the money back; weekly orders are paid day by day at the cut-off.
 */
beforeEach(function (): void {
    // Sunday 20 September 2026, 06:00: today's orders close at 07:30.
    $this->travelTo(Carbon::parse('2026-09-20 06:00:00'));

    $this->world = PosWorld::create(stock: 100, price: 5);
    StudentWorld::enableSchool($this->world);
    $this->student = StudentWorld::enrol($this->world);
    $this->token = StudentWorld::parentToken($this->student->guardians->first());
    StudentWorld::enablePreOrders($this->world, [(int) $this->world->product->main_category_id]);
});

function payDay($test, string $date, int $quantity = 2, array $extra = [])
{
    app('auth')->forgetGuards();

    return $test->withToken($test->token)->putJson(
        $test->world->url("/api/v1/parent/students/{$test->student->id}/pre-orders/days/{$date}"),
        ['items' => [['product_id' => $test->world->product->id, 'quantity' => $quantity]], ...$extra],
    );
}

function cardBalance($test): float
{
    return (new GetBalanceAction())->execute($test->student->id);
}

it('pays a day order from the card as a completed sale dated the day it is served', function (): void {
    StudentWorld::topUp($this->world, $this->student, 50);

    $response = payDay($this, '2026-09-22', 3)->assertOk()->assertJsonPath('message', 'Pre-order paid');

    $sale = Sale::sole();
    expect(collect($response->json('data.days'))->firstWhere('date', '2026-09-22')['paid'])->toBeTrue()
        ->and($sale->status)->toBe('completed')
        ->and($sale->source)->toBe('pre_order')
        ->and(Carbon::parse($sale->date)->toDateString())->toBe('2026-09-22')
        ->and($sale->branch_id)->toBe($this->world->branch->id)
        ->and($sale->account_id)->toBe($this->student->id)
        ->and((float) $sale->grand_total)->toBe(15.0)
        ->and(StudentPreOrderCollection::sole()->sale_id)->toBe($sale->id)
        ->and(cardBalance($this))->toBe(35.0);
});

it('keeps a pre-order sale out of the till\'s open day session', function (): void {
    StudentWorld::topUp($this->world, $this->student, 50);
    SaleDaySession::create(['branch_id' => $this->world->branch->id, 'opened_by' => $this->world->user->id, 'opened_at' => now(), 'opening_amount' => 0, 'status' => 'open']);

    payDay($this, '2026-09-22')->assertOk();

    expect(Sale::sole()->sale_day_session_id)->toBeNull()
        ->and(Carbon::parse(Sale::sole()->date)->toDateString())->toBe('2026-09-22');
});

it('saves nothing when the card cannot cover the order', function (): void {
    StudentWorld::topUp($this->world, $this->student, 5);

    payDay($this, '2026-09-22', 3)
        ->assertStatus(422)
        ->assertJsonPath('message', "There isn't enough on {$this->student->name}'s card for this order (15.00). Please top up, or pay by bank card.");

    expect(StudentPreOrder::count())->toBe(0)
        ->and(Sale::count())->toBe(0)
        ->and(cardBalance($this))->toBe(5.0);
});

it('saves a bank card order unpaid and pays it once the money lands on the card', function (): void {
    payDay($this, '2026-09-22', 2, ['pay' => 'bank'])
        ->assertOk()
        ->assertJsonPath('message', 'Pre-order saved. Pay by bank card to confirm it.');
    expect(Sale::count())->toBe(0);

    StudentWorld::topUp($this->world, $this->student, 10);
    $result = (new ChargeDueAction())->forStudent($this->student->id);

    expect($result['paid'])->toBe(1)
        ->and(Carbon::parse(Sale::sole()->date)->toDateString())->toBe('2026-09-22')
        ->and(cardBalance($this))->toBe(0.0);
});

it('refunds a paid day when it changes, and keeps the sale when it is saved unchanged', function (): void {
    StudentWorld::topUp($this->world, $this->student, 50);
    payDay($this, '2026-09-22', 2)->assertOk();
    $first = Sale::sole();

    payDay($this, '2026-09-22', 2)->assertOk();
    expect(Sale::count())->toBe(1);

    payDay($this, '2026-09-22', 4)->assertOk();

    expect($first->fresh()->status)->toBe('cancelled')
        ->and(Sale::where('status', 'completed')->sole()->grand_total)->toEqual(20)
        ->and(StudentPreOrderCollection::count())->toBe(1)
        ->and(cardBalance($this))->toBe(30.0);
});

it('gives the money back when a paid day is removed', function (): void {
    StudentWorld::topUp($this->world, $this->student, 50);
    payDay($this, '2026-09-22', 2)->assertOk();

    app('auth')->forgetGuards();
    $this->withToken($this->token)->deleteJson($this->world->url("/api/v1/parent/students/{$this->student->id}/pre-orders/days/2026-09-22"))->assertOk();

    expect(Sale::sole()->status)->toBe('cancelled')
        ->and(StudentPreOrderCollection::count())->toBe(0)
        ->and(cardBalance($this))->toBe(50.0);
});

it('pays weekly orders day by day when the day closes, and leaves a short card unpaid', function (): void {
    StudentWorld::topUp($this->world, $this->student, 12);
    $poor = StudentWorld::enrol($this->world, ['name' => 'Short Card', 'card_uid' => '0A0B0C0D', 'guardians' => []]);
    foreach ([$this->student, $poor] as $child) {
        $order = StudentPreOrder::create(['account_id' => $child->id, 'type' => 'weekly', 'weekdays' => [7, 1], 'status' => 'active']);
        $order->items()->create(['product_id' => $this->world->product->id, 'quantity' => 2, 'tenant_id' => $order->tenant_id]);
    }

    $this->travelTo(Carbon::parse('2026-09-20 07:30:30'));
    $this->artisan('student:pre-order-close')->assertSuccessful();

    $summary = (new SummaryAction())->execute(Carbon::parse('2026-09-20'));
    expect(Sale::sole()->account_id)->toBe($this->student->id)
        ->and(Carbon::parse(Sale::sole()->date)->toDateString())->toBe('2026-09-20')
        ->and(cardBalance($this))->toBe(2.0)
        ->and($summary['students'])->toBe(1)
        ->and($summary['unpaid'])->toBe(1)
        ->and(collect($summary['orders'])->firstWhere('paid', false)['student'])->toBe('Short Card');

    // Monday is not charged until Monday's orders close.
    expect(StudentPreOrderCollection::whereDate('date', '2026-09-21')->count())->toBe(0);
});

it('prints the day\'s list with student details and item quantities only', function (): void {
    StudentWorld::topUp($this->world, $this->student, 50);
    payDay($this, '2026-09-22', 2, ['note' => 'No sauce'])->assertOk();

    $html = view('print.student.pre-orders', [
        ...(new App\Actions\Student\PreOrder\PrintListAction())->execute('2026-09-22'),
        'companyName' => 'Canteen',
        'companyLogo' => null,
    ])->render();

    expect($html)->toContain($this->student->name)
        ->toContain('2 ×')
        ->toContain('No sauce')
        // For the kitchen and class delivery: names and quantities, no prices or sale numbers.
        ->not->toContain('QAR')
        ->not->toContain(Sale::sole()->invoice_no);

    $this->actingAs($this->world->user)
        ->get($this->world->url(route('print::student::pre-orders', ['date' => '2026-09-22'], false)))
        ->assertForbidden();
});

it('builds the print list from the date in the URL', function (): void {
    StudentWorld::topUp($this->world, $this->student, 50);
    payDay($this, '2026-09-22', 2)->assertOk();

    $data = (new App\Actions\Student\PreOrder\PrintListAction())->execute('2026-09-22');
    $html = view('print.student.pre-orders', [...$data, 'companyName' => 'Canteen', 'companyLogo' => null])->render();

    expect($data['sections'])->toHaveCount(1)
        ->and($data['sections'][0]['groups'])->toHaveCount(1)
        ->and($data['locked'])->toBeFalse()
        ->and($html)->toContain($this->student->name)->toContain('2 ×')
        ->toContain('Grade 5 · Class B')
        ->not->toContain('Grade Grade')
        ->and(App\Actions\Student\PreOrder\PrintListAction::gradeClass(['grade' => '4', 'section' => 'a']))->toBe('Grade 4 · Class A');
});

it('keeps taking orders when no pre-order branch was chosen, booking them at the first branch', function (): void {
    StudentWorld::enablePreOrders($this->world, [(int) $this->world->product->main_category_id], ['pre_order_branch_id' => null]);
    StudentWorld::topUp($this->world, $this->student, 50);

    app('auth')->forgetGuards();
    $this->withToken($this->token)->getJson($this->world->url("/api/v1/parent/students/{$this->student->id}"))
        ->assertOk()
        ->assertJsonPath('data.pre_orders.enabled', true);

    payDay($this, '2026-09-22')->assertOk();

    expect(Sale::sole()->branch_id)->toBe(App\Models\Branch::query()->orderBy('id')->value('id'));
});

it('refunds a paid day that is cancelled by skipping it, so the weekly order does not charge it again', function (): void {
    StudentWorld::topUp($this->world, $this->student, 50);
    $weekly = StudentPreOrder::create(['account_id' => $this->student->id, 'type' => 'weekly', 'weekdays' => [2], 'status' => 'active']);
    $weekly->items()->create(['product_id' => $this->world->product->id, 'quantity' => 1, 'tenant_id' => $weekly->tenant_id]);
    payDay($this, '2026-09-22', 3)->assertOk();

    app('auth')->forgetGuards();
    $this->withToken($this->token)->postJson($this->world->url("/api/v1/parent/students/{$this->student->id}/pre-orders/days/2026-09-22/skip"))->assertOk();

    // Tuesday's orders close at 07:30 on Tuesday.
    $this->travelTo(Carbon::parse('2026-09-22 07:31:00'));
    $this->artisan('student:pre-order-close')->assertSuccessful();

    expect(Sale::sole()->status)->toBe('cancelled')
        ->and(StudentPreOrderCollection::count())->toBe(0)
        ->and(cardBalance($this))->toBe(50.0);
});

it('does not let the school overdraft pay for a pre-order', function (): void {
    StudentWorld::enablePreOrders($this->world, [(int) $this->world->product->main_category_id], ['overdraft_limit' => 50]);
    StudentWorld::topUp($this->world, $this->student, 9);

    payDay($this, '2026-09-22', 2)
        ->assertStatus(422)
        ->assertJsonPath('message', "There isn't enough on {$this->student->name}'s card for this order (10.00). Please top up, or pay by bank card.");

    payDay($this, '2026-09-22', 2, ['pay' => 'bank'])->assertOk();
    expect(Sale::count())->toBe(0)
        ->and(cardBalance($this))->toBe(9.0);
});

it('offers the pre-order list on the sales list to staff who can see the canteen menu', function (): void {
    Livewire\Livewire::actingAs($this->world->user)->test(App\Livewire\Sale\Table::class)
        ->assertDontSee('Pre-order list for');

    $this->world->user->givePermissionTo(Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'student menu.view', 'guard_name' => 'web']));
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    Livewire\Livewire::actingAs($this->world->user->fresh())->test(App\Livewire\Sale\Table::class)
        ->set('from_date', '2026-09-22')
        ->set('to_date', '2026-09-22')
        ->assertSee('Pre-order list for')
        ->assertSee('2026-09-22');
});

it('books a student\'s pre-order at their own branch', function (): void {
    $lusail = $this->world->addBranch('AIA - Lusail', 'AIAL');
    $this->student->studentDetail->update(['branch_id' => $lusail->id]);
    StudentWorld::topUp($this->world, $this->student, 50);

    payDay($this, '2026-09-22')->assertOk();

    expect(Sale::sole()->branch_id)->toBe($lusail->id);
});

it('prints each branch on its own, or one branch on request', function (): void {
    $lusail = $this->world->addBranch('AIA-LUSAIL', 'AIAL');
    $other = StudentWorld::enrol($this->world, ['name' => 'Lusail Child', 'admission_no' => 'L-1', 'card_uid' => '0A0B0C0D', 'guardians' => []]);
    $other->studentDetail->update(['branch_id' => $lusail->id]);
    StudentWorld::topUp($this->world, $this->student, 50);
    StudentWorld::topUp($this->world, $other, 50);
    payDay($this, '2026-09-22', 2)->assertOk();
    $order = StudentPreOrder::create(['account_id' => $other->id, 'type' => 'day', 'date' => '2026-09-22', 'status' => 'active']);
    $order->items()->create(['product_id' => $this->world->product->id, 'quantity' => 3, 'tenant_id' => $order->tenant_id]);
    expect((new App\Actions\Student\PreOrder\ChargeAction())->execute($order, Carbon::parse('2026-09-22'))['success'])->toBeTrue();

    $all = (new App\Actions\Student\PreOrder\PrintListAction())->execute('2026-09-22');
    $byBranch = collect($all['sections'])->keyBy('branch');
    expect($all['sections'])->toHaveCount(2)
        ->and($byBranch['AIA-LUSAIL']['items'][0]['quantity'])->toBe(3)
        ->and($byBranch[$this->world->branch->name]['items'][0]['quantity'])->toBe(2);

    $one = (new App\Actions\Student\PreOrder\PrintListAction())->execute('2026-09-22', $lusail->id);
    $html = view('print.student.pre-orders', [...$one, 'companyName' => 'Canteen', 'companyLogo' => null])->render();
    expect($one['sections'])->toHaveCount(1)
        ->and($html)->toContain('Lusail Child')->toContain('AIA-LUSAIL')
        ->not->toContain($this->student->name);
});
