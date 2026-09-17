<?php

use App\Livewire\Settings\StudentConfiguration;
use App\Livewire\Student\CanteenMenu;
use App\Models\Product;
use App\Models\StudentPreOrder;
use App\Models\StudentPreOrderCollection;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * Canteen pre-orders: a parent picks items in the portal for a day or every week;
 * when the card is tapped the till gets today's items to put in the cart, and the
 * completed card sale marks the order collected. Nothing is charged at ordering.
 */
beforeEach(function (): void {
    // Sunday 20 September 2026, before the 07:30 cut-off.
    $this->travelTo(Carbon::parse('2026-09-20 06:00:00'));

    $this->world = PosWorld::create(stock: 100, price: 5);
    StudentWorld::enableSchool($this->world);
    $this->student = StudentWorld::enrol($this->world);
    $this->guardian = $this->student->guardians->first();
    $this->token = StudentWorld::parentToken($this->guardian);

    $this->menuCategory = (int) $this->world->product->main_category_id;
    $this->offMenu = Product::create([
        'tenant_id' => $this->world->tenant->id, 'type' => 'product', 'name' => 'Stationery Kit', 'code' => 'STAT1',
        'unit_id' => $this->world->product->unit_id, 'department_id' => $this->world->product->department_id,
        'main_category_id' => DB::table('categories')->insertGetId(['tenant_id' => $this->world->tenant->id, 'name' => 'Stationery']),
        'mrp' => 12, 'created_by' => $this->world->user->id, 'updated_by' => $this->world->user->id,
    ]);

    StudentWorld::enablePreOrders($this->world, [$this->menuCategory]);
});

function preOrderJson($test, string $method, string $path, array $data = [])
{
    app('auth')->forgetGuards();

    return $test->withToken($test->token)->json($method, $test->world->url('/api/v1/parent/'.ltrim($path, '/')), $data);
}

function tillLookup($test)
{
    app('auth')->forgetGuards();
    Sanctum::actingAs($test->world->user);

    return $test->withoutToken()->getJson($test->world->url('/api/v1/students/card/04A21B9C'));
}

it('shows parents only the categories the school put on the menu', function (): void {
    $response = preOrderJson($this, 'GET', 'pre-order-menu')->assertOk();

    $ids = collect($response->json('data'))->flatMap(fn ($group) => collect($group['items'])->pluck('id'))->all();
    expect($ids)->toContain($this->world->product->id)->not->toContain($this->offMenu->id);
});

it('shows no menu and refuses orders while pre-orders are off', function (): void {
    StudentWorld::enablePreOrders($this->world, [$this->menuCategory], ['pre_orders_enabled' => false]);

    preOrderJson($this, 'GET', 'pre-order-menu')->assertOk()->assertJsonPath('data', []);
    preOrderJson($this, 'PUT', "students/{$this->student->id}/pre-orders/days/2026-09-21", ['items' => [['product_id' => $this->world->product->id, 'quantity' => 1]]])
        ->assertStatus(422);
});

it('saves a day order and a weekly order, and the day order wins on its day', function (): void {
    preOrderJson($this, 'PUT', "students/{$this->student->id}/pre-orders/weekly", [
        'weekdays' => [7, 1, 2, 3, 4, 5], // Friday is not a school day and is dropped
        'items' => [['product_id' => $this->world->product->id, 'quantity' => 1]],
    ])->assertOk()->assertJsonPath('data.weekly.weekdays', [1, 2, 3, 4, 7]);

    $response = preOrderJson($this, 'PUT', "students/{$this->student->id}/pre-orders/days/2026-09-21", [
        'items' => [['product_id' => $this->world->product->id, 'quantity' => 3]],
        'note' => 'No sauce please',
    ])->assertOk();

    $days = collect($response->json('data.days'))->keyBy('date');
    expect($days['2026-09-20']['source'])->toBe('weekly')
        ->and($days['2026-09-21']['source'])->toBe('day')
        ->and($days['2026-09-21']['order']['items_count'])->toBe(3)
        ->and($days['2026-09-21']['order']['total'])->toEqual(15)
        ->and($days['2026-09-22']['source'])->toBe('weekly')
        // Friday and Saturday are not school days.
        ->and($days->has('2026-09-25'))->toBeFalse();
});

it('skips a day of the weekly order and can bring it back', function (): void {
    preOrderJson($this, 'PUT', "students/{$this->student->id}/pre-orders/weekly", [
        'weekdays' => [7, 1, 2, 3, 4],
        'items' => [['product_id' => $this->world->product->id, 'quantity' => 2]],
    ])->assertOk();

    $skipped = preOrderJson($this, 'POST', "students/{$this->student->id}/pre-orders/days/2026-09-21/skip")->assertOk();
    expect(collect($skipped->json('data.days'))->firstWhere('date', '2026-09-21')['source'])->toBe('skipped');

    $cleared = preOrderJson($this, 'DELETE', "students/{$this->student->id}/pre-orders/days/2026-09-21")->assertOk();
    expect(collect($cleared->json('data.days'))->firstWhere('date', '2026-09-21')['source'])->toBe('weekly');

    preOrderJson($this, 'POST', "students/{$this->student->id}/pre-orders/weekly/pause")->assertOk()->assertJsonPath('data.weekly.status', 'paused');
    expect(tillLookup($this)->json('data.pre_order'))->toBeNull();
});

it('refuses items off the menu, closed days and changes after the cut-off', function (): void {
    $path = "students/{$this->student->id}/pre-orders/days";

    preOrderJson($this, 'PUT', "$path/2026-09-21", ['items' => [['product_id' => $this->offMenu->id, 'quantity' => 1]]])
        ->assertStatus(422)->assertJsonPath('message', 'Some items are no longer on the pre-order menu. Please check the order again.');

    // Friday: the canteen is closed.
    preOrderJson($this, 'PUT', "$path/2026-09-25", ['items' => [['product_id' => $this->world->product->id, 'quantity' => 1]]])
        ->assertStatus(422);

    $this->travelTo(Carbon::parse('2026-09-20 08:00:00'));
    preOrderJson($this, 'PUT', "$path/2026-09-20", ['items' => [['product_id' => $this->world->product->id, 'quantity' => 1]]])
        ->assertStatus(422)->assertJsonPath('message', 'Orders for today closed at 7:30 AM.');
    preOrderJson($this, 'PUT', "$path/2026-09-21", ['items' => [['product_id' => $this->world->product->id, 'quantity' => 1]]])
        ->assertOk();
});

it('is a 404 for another family\'s child', function (): void {
    $other = StudentWorld::enrol($this->world, [
        'name' => 'Other Child', 'card_uid' => '0A0B0C0D',
        'guardians' => [['name' => 'Other Parent', 'mobile' => '66000000', 'relation' => 'mother']],
    ]);

    preOrderJson($this, 'GET', "students/{$other->id}/pre-orders")->assertNotFound();
    preOrderJson($this, 'PUT', "students/{$other->id}/pre-orders/days/2026-09-21", ['items' => [['product_id' => $this->world->product->id, 'quantity' => 1]]])
        ->assertNotFound();

    expect(StudentPreOrder::count())->toBe(0);
});

it('hands today\'s pre-order to the till and marks it collected by the card sale', function (): void {
    StudentWorld::topUp($this->world, $this->student, 100);
    preOrderJson($this, 'PUT', "students/{$this->student->id}/pre-orders/weekly", [
        'weekdays' => [7, 1, 2, 3, 4],
        'items' => [['product_id' => $this->world->product->id, 'quantity' => 2]],
        'note' => 'Lunch break',
    ])->assertOk();

    // Later that morning, after the cut-off, the card is tapped.
    $this->travelTo(Carbon::parse('2026-09-20 10:15:00'));
    $lookup = tillLookup($this)->assertOk();
    $preOrder = $lookup->json('data.pre_order');

    expect($preOrder['source'])->toBe('weekly')
        ->and($preOrder['note'])->toBe('Lunch break')
        ->and($preOrder['total'])->toEqual(10)
        ->and($preOrder['items'][0]['quantity'])->toBe(2)
        ->and($preOrder['items'][0]['product']['id'])->toBe($this->world->product->id)
        ->and($preOrder['items'][0]['product']['mrp'])->toEqual(5);

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'customerName' => $this->student->name,
        'studentAccountId' => $this->student->id,
        'cardUid' => '04A21B9C',
        'paymentMethod' => 'student_card',
        'items' => [['productId' => $this->world->product->id, 'quantity' => 2, 'unitPrice' => 5, 'discount' => 0]],
        'totalPayment' => 10,
        'preOrderId' => $preOrder['id'],
    ]))->assertCreated();

    $collection = StudentPreOrderCollection::sole();
    expect($collection->date->toDateString())->toBe('2026-09-20')
        ->and($collection->account_id)->toBe($this->student->id)
        ->and(tillLookup($this)->json('data.pre_order'))->toBeNull();

    // Collected today only: Monday's tap gets it again.
    $this->travelTo(Carbon::parse('2026-09-21 10:00:00'));
    expect(tillLookup($this)->json('data.pre_order.id'))->toBe($preOrder['id']);
});

it('ignores a pre-order id that does not belong to the sale\'s student', function (): void {
    StudentWorld::topUp($this->world, $this->student, 100);
    $other = StudentWorld::enrol($this->world, ['name' => 'Other Child', 'card_uid' => '0A0B0C0D', 'guardians' => []]);
    $order = StudentPreOrder::create(['account_id' => $other->id, 'type' => 'weekly', 'weekdays' => [7], 'status' => 'active']);
    $order->items()->create(['product_id' => $this->world->product->id, 'quantity' => 1, 'tenant_id' => $order->tenant_id]);

    Sanctum::actingAs($this->world->user);
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'customerName' => $this->student->name,
        'studentAccountId' => $this->student->id,
        'cardUid' => '04A21B9C',
        'paymentMethod' => 'student_card',
        'totalPayment' => 5,
        'preOrderId' => $order->id,
    ]))->assertCreated();

    expect(StudentPreOrderCollection::count())->toBe(0);
});

it('lets the school set up pre-orders in Settings', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['tenant_id' => $this->world->tenant->id, 'name' => 'student settings.edit', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);

    Livewire::test(StudentConfiguration::class)
        ->set('pre_orders_enabled', true)
        ->set('pre_order_category_ids', [])
        ->call('save')
        ->assertHasErrors('pre_order_category_ids')
        ->set('pre_order_category_ids', [(string) $this->menuCategory])
        ->set('school_days', ['1', '2'])
        ->set('pre_order_cutoff', '08:15')
        ->call('save')
        ->assertHasNoErrors();

    $settings = StudentSettings::current();
    expect($settings->preOrdersOpen())->toBeTrue()
        ->and($settings->schoolDays)->toBe([1, 2])
        ->and($settings->preOrderCutoff)->toBe('08:15');
});

it('shows each day\'s dishes and only takes orders on days the meal is served', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['tenant_id' => $this->world->tenant->id, 'name' => 'student menu.edit', 'guard_name' => 'web']));
    $this->actingAs($this->world->user);

    Livewire::test(CanteenMenu::class)
        ->assertSet('product_id', $this->world->product->id)
        ->set('courses.0.name', 'Main dish')
        ->set('courses.0.note', 'Mini portions of 150 g')
        ->set('courses.0.dishes.7.name', 'Cheesy Pasta Twirls')
        ->set('courses.0.dishes.7.description', 'Cheesy chicken pasta twirls')
        ->set('courses.0.dishes.1.name', 'Mini Beef Kofta')
        ->call('save')
        ->assertDispatched('success');

    $meal = collect(preOrderJson($this, 'GET', 'pre-order-menu')->assertOk()->json('data.0.items'))->firstWhere('id', $this->world->product->id);
    expect($meal['served_weekdays'])->toBe([1, 7])
        ->and(collect($meal['menu'])->firstWhere('weekday', 7)['dishes'][0])->toBe([
            'course' => 'Main dish', 'note' => 'Mini portions of 150 g', 'name' => 'Cheesy Pasta Twirls', 'description' => 'Cheesy chicken pasta twirls',
        ]);

    $item = ['items' => [['product_id' => $this->world->product->id, 'quantity' => 1]]];
    // Monday is served, Tuesday is not.
    preOrderJson($this, 'PUT', "students/{$this->student->id}/pre-orders/days/2026-09-21", $item)->assertOk();
    preOrderJson($this, 'PUT', "students/{$this->student->id}/pre-orders/days/2026-09-22", $item)->assertStatus(422);
    preOrderJson($this, 'PUT', "students/{$this->student->id}/pre-orders/weekly", $item + ['weekdays' => [7, 1, 2]])->assertStatus(422);
    preOrderJson($this, 'PUT', "students/{$this->student->id}/pre-orders/weekly", $item + ['weekdays' => [7, 1]])->assertOk();
});

it('keeps the canteen menu read-only without the edit permission', function (): void {
    $this->actingAs($this->world->user);

    Livewire::test(CanteenMenu::class)->call('save')->assertForbidden();
});
