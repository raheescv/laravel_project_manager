<?php

use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Actions\Sale\UpdateAction as SaleUpdateAction;
use App\Actions\SaleReturn\CreateAction as SaleReturnCreateAction;
use App\Actions\Student\DeleteAction as StudentDeleteAction;
use App\Actions\Student\GetBalanceAction;
use App\Models\Account;
use App\Models\Guardian;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\StudentDetail;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * A student card is the student account's own ledger. Top-ups credit it, sales
 * debit it, and a sale paid by "Student Card" moves no money of its own — so the
 * card balance can never drift from the books. The overdraft limit is enforced
 * inside the sale's save, for every channel.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 100, price: 50);
    StudentWorld::enableSchool($this->world);
    $this->student = StudentWorld::enrol($this->world);
});

function studentBalance(Account $student): float
{
    return (new GetBalanceAction())->execute($student->id);
}

it('enrols a student as an account with only school fields in student_details', function (): void {
    $student = $this->student;

    expect($student->model)->toBe('student')
        ->and($student->account_type)->toBe('liability')
        ->and($student->accountCategory->name)->toBe('Student Card Balances')
        ->and($student->studentDetail->grade)->toBe('Grade 5')
        ->and($student->studentDetail->card_uid)->toBe('04A21B9C')
        ->and($student->guardians)->toHaveCount(1)
        ->and($student->guardians->first()->pivot->is_primary)->toBeTruthy()
        ->and(Account::idBySlug('student_card'))->toBeInt();
});

it('spends the card balance on a Student Card sale without a payment leg', function (): void {
    StudentWorld::topUp($this->world, $this->student, 100);
    expect(studentBalance($this->student))->toBe(100.0);

    $response = (new SaleCreateAction())->execute(StudentWorld::salePayload($this->world, $this->student->id, 30, card: 30), $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message']);
    $sale = $response['data']->refresh();

    expect((float) $sale->paid)->toBe(30.0)
        ->and((float) $sale->balance)->toBe(0.0)
        ->and($sale->payment_method_name)->toBe('Student Wallet')
        ->and(studentBalance($this->student))->toBe(70.0)
        // The Student Card method never carries a balance.
        ->and(JournalEntry::where('account_id', Account::idBySlug('student_card'))->exists())->toBeFalse();

    $entries = JournalEntry::where('model', 'Sale')->where('model_id', $sale->id)->get();
    expect(round($entries->sum('debit'), 2))->toBe(round($entries->sum('credit'), 2));
});

it('lets a card go into overdraft down to the school limit and no further', function (): void {
    StudentWorld::setOverdraft($this->world, 30);
    StudentWorld::topUp($this->world, $this->student, 5);

    $ok = (new SaleCreateAction())->execute(StudentWorld::salePayload($this->world, $this->student->id, 35, card: 35), $this->world->user->id);
    expect($ok['success'])->toBeTrue($ok['message'])
        ->and(studentBalance($this->student))->toBe(-30.0);

    DB::beginTransaction();
    $refused = (new SaleCreateAction())->execute(StudentWorld::salePayload($this->world, $this->student->id, 0.01, card: 0.01), $this->world->user->id);
    DB::rollBack();

    expect($refused['success'])->toBeFalse()
        ->and($refused['message'])->toContain('card cannot cover')
        ->and(studentBalance($this->student))->toBe(-30.0)
        ->and(Sale::withoutGlobalScopes()->count())->toBe(1);
});

it('counts a cash part of a split payment against nothing on the card', function (): void {
    StudentWorld::topUp($this->world, $this->student, 10);

    $response = (new SaleCreateAction())->execute(StudentWorld::salePayload($this->world, $this->student->id, 25, card: 10, cash: 15), $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        ->and(studentBalance($this->student))->toBe(0.0);
});

it('refuses Student Card for anyone who is not a student', function (): void {
    StudentWorld::topUp($this->world, $this->student, 100);

    $response = (new SaleCreateAction())->execute(
        StudentWorld::salePayload($this->world, $this->world->accounts['general_customer'], 20, card: 20),
        $this->world->user->id,
    );

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('student\'s own purchase');
});

it('checks an edited card sale against the balance without the sale being edited', function (): void {
    $permission = Permission::firstOrCreate(['tenant_id' => $this->world->tenant->id, 'name' => 'sale.edit completed', 'guard_name' => 'web']);
    $this->world->user->givePermissionTo($permission);
    $this->actingAs($this->world->user);

    StudentWorld::topUp($this->world, $this->student, 10);
    $sale = (new SaleCreateAction())->execute(StudentWorld::salePayload($this->world, $this->student->id, 10, card: 10), $this->world->user->id)['data']->refresh();
    expect(studentBalance($this->student))->toBe(0.0);

    $editTo = function (float $amount) use ($sale) {
        // What the sale page posts back: the saved rows with the new amounts.
        $payload = StudentWorld::salePayload($this->world, $this->student->id, $amount, card: $amount);
        $payload['total'] = $amount;
        $payload['items'] = $sale->items()->get()->map(fn ($item) => array_merge($item->toArray(), ['unit_price' => $amount]))->all();
        $payload['payments'] = $sale->payments()->get()->map(fn ($payment) => array_merge($payment->toArray(), ['amount' => $amount]))->all();

        DB::beginTransaction();
        $response = (new SaleUpdateAction())->execute($payload, $sale->id, $this->world->user->id);
        $response['success'] ? DB::commit() : DB::rollBack();

        return $response;
    };

    StudentWorld::setOverdraft($this->world, 5);
    // 10 available before this sale + 5 overdraft: 14 fits even though the balance reads 0 now.
    $edited = $editTo(14);
    expect($edited['success'])->toBeTrue($edited['message'])
        ->and(studentBalance($this->student))->toBe(-4.0);

    $refused = $editTo(16);
    expect($refused['success'])->toBeFalse()
        ->and(studentBalance($this->student))->toBe(-4.0);
});

it('puts a return refunded to the card back on the balance', function (): void {
    StudentWorld::topUp($this->world, $this->student, 50);
    $sale = (new SaleCreateAction())->execute(StudentWorld::salePayload($this->world, $this->student->id, 20, card: 20), $this->world->user->id)['data'];
    expect(studentBalance($this->student))->toBe(30.0);

    $payload = StudentWorld::salePayload($this->world, $this->student->id, 20, card: 20);
    $payload['sale_id'] = $sale->id;
    $response = (new SaleReturnCreateAction())->execute($payload, $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        ->and(studentBalance($this->student))->toBe(50.0);
});

it('links brothers and sisters to one parent login by mobile', function (): void {
    $sibling = StudentWorld::enrol($this->world, [
        'name' => 'Omar Ahmed',
        'card_uid' => '04B7C8D9',
        'guardians' => [['name' => 'Ahmed Saleh', 'mobile' => '5512 3456', 'relation' => 'father']],
    ]);

    expect(Guardian::count())->toBe(1)
        ->and(Guardian::first()->students()->pluck('accounts.id')->all())->toEqualCanonicalizing([$this->student->id, $sibling->id]);
});

it('allows students with the same name and no mobile', function (): void {
    $twin = StudentWorld::enrol($this->world, ['name' => 'Sara Ahmed', 'card_uid' => null, 'guardians' => []]);

    expect($twin->id)->not->toBe($this->student->id);
});

it('refuses a duplicate admission number or card', function (): void {
    $response = (new \App\Actions\Student\CreateAction())->execute([
        'name' => 'Another', 'admission_no' => $this->student->studentDetail->admission_no, 'status' => 'active',
    ], $this->world->user->id);
    expect($response['success'])->toBeFalse();

    $response = (new \App\Actions\Student\Card\AssignAction())->execute(
        StudentWorld::enrol($this->world, ['name' => 'Other', 'card_uid' => null])->id,
        '04 A2 1B 9C',
        $this->world->user->id,
    );
    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('already linked');
});

it('will not delete a student who has card history', function (): void {
    StudentWorld::topUp($this->world, $this->student, 10);

    $response = (new StudentDeleteAction())->execute($this->student->id, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and(StudentDetail::where('account_id', $this->student->id)->exists())->toBeTrue();
});
