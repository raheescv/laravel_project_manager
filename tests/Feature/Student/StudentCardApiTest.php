<?php

use App\Actions\Student\Card\BlockAction;
use App\Actions\Student\GetBalanceAction;
use App\Models\Account;
use App\Models\Sale;
use App\Models\StudentDetail;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * QLOUD POS reads the student's NFC card: the lookup shows who it is and what the
 * card can spend, and a sale sent with the card is paid from the card balance —
 * online only, for that student only, never on a blocked card.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 100, price: 20);
    StudentWorld::enableSchool($this->world);
    $this->student = StudentWorld::enrol($this->world);
    StudentWorld::setOverdraft($this->world, 10);
    StudentWorld::topUp($this->world, $this->student, 30);
    Sanctum::actingAs($this->world->user);
});

function cardSalePayload(PosWorld $world, Account $student, array $overrides = []): array
{
    return $world->salePayload(array_merge([
        'customerName' => $student->name,
        'studentAccountId' => $student->id,
        'cardUid' => '04:A2:1B:9C',
        'paymentMethod' => 'student_card',
        'totalPayment' => 20,
    ], $overrides));
}

it('looks up a tapped card with balance and what can be spent', function (): void {
    $this->getJson($this->world->url('/api/v1/students/card/04a21b9c'))
        ->assertOk()
        ->assertJsonPath('data.account_id', $this->student->id)
        ->assertJsonPath('data.name', 'Sara Ahmed')
        ->assertJsonPath('data.balance', 30)
        ->assertJsonPath('data.available', 40)
        ->assertJsonPath('data.card_status', 'active');
});

it('is a 404 for an unknown card and a 422 for a blocked one', function (): void {
    $this->getJson($this->world->url('/api/v1/students/card/FFFFFFFF'))->assertNotFound();

    (new BlockAction())->execute($this->student->id, 'user', $this->world->user->id, 'Lost');
    $this->getJson($this->world->url('/api/v1/students/card/04A21B9C'))
        ->assertUnprocessable()
        ->assertJsonPath('data.card_status', 'blocked');
});

it('rings a sale paid from the student card', function (): void {
    $response = $this->postJson($this->world->url('/api/v1/sale'), cardSalePayload($this->world, $this->student))
        ->assertCreated()
        ->assertJsonPath('data.payments.0.method', 'Student Wallet')
        ->assertJsonPath('data.student.account_id', $this->student->id)
        ->assertJsonPath('data.student.card_balance', 10);

    $sale = Sale::withoutGlobalScopes()->find($response->json('data.id'));
    expect($sale->account_id)->toBe($this->student->id)
        ->and((float) $sale->balance)->toBe(0.0);
});

it('splits a sale between the card and cash', function (): void {
    $this->postJson($this->world->url('/api/v1/sale'), cardSalePayload($this->world, $this->student, [
        'items' => [['productId' => $this->world->product->id, 'quantity' => 3, 'unitPrice' => 20, 'discount' => 0]],
        'paymentMethod' => 'custom',
        'totalPayment' => 60,
        'payments' => [
            ['payment_method_id' => Account::idBySlug('student_card'), 'amount' => 40],
            ['payment_method_id' => $this->world->cashAccountId, 'amount' => 20],
        ],
    ]))->assertCreated();

    expect((new GetBalanceAction())->execute($this->student->id))->toBe(-10.0);
});

it('refuses a card sale beyond balance plus overdraft', function (): void {
    $this->postJson($this->world->url('/api/v1/sale'), cardSalePayload($this->world, $this->student, [
        'items' => [['productId' => $this->world->product->id, 'quantity' => 3, 'unitPrice' => 20, 'discount' => 0]],
        'totalPayment' => 60,
    ]))->assertUnprocessable()->assertJsonFragment(['success' => false]);

    expect(Sale::withoutGlobalScopes()->count())->toBe(0)
        ->and((new GetBalanceAction())->execute($this->student->id))->toBe(30.0);
});

it('refuses a card sale queued offline, a wrong card, or a blocked card', function (array $overrides, ?callable $setup, string $message): void {
    $setup && $setup($this);

    $response = $this->postJson($this->world->url('/api/v1/sale'), cardSalePayload($this->world, $this->student, $overrides))
        ->assertUnprocessable()
        ->assertJsonFragment(['success' => false]);

    expect($response->json('message'))->toContain($message)
        ->and(Sale::withoutGlobalScopes()->count())->toBe(0);
})->with([
    'offline' => [['offlineRef' => 'OFF-1', 'clientUuid' => '1f0f6f0e-4b6a-4d8b-9d7f-9c1f0a1b2c3d'], null, 'connection'],
    'wrong card' => [['cardUid' => 'DEADBEEF'], null, 'does not belong'],
    'blocked' => [[], fn ($test) => StudentDetail::where('account_id', $test->student->id)->update(['card_status' => 'blocked']), 'blocked'],
]);

it('will not let an ordinary customer pay with Student Card', function (): void {
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload(['paymentMethod' => 'student_card', 'totalPayment' => 20]))
        ->assertUnprocessable();

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'paymentMethod' => 'custom',
        'totalPayment' => 20,
        'payments' => [['payment_method_id' => Account::idBySlug('student_card'), 'amount' => 20]],
    ]))->assertUnprocessable();
});

it('links a card from the app only with permission', function (): void {
    $other = StudentWorld::enrol($this->world, ['name' => 'Omar', 'card_uid' => null, 'guardians' => []]);

    $this->postJson($this->world->url("/api/v1/students/{$other->id}/card"), ['cardUid' => '11:22:33:44'])->assertForbidden();

    $this->world->user->givePermissionTo(Permission::firstOrCreate(['tenant_id' => $this->world->tenant->id, 'name' => 'student card.assign', 'guard_name' => 'web']));

    $this->getJson($this->world->url('/api/v1/students?search=Omar'))->assertOk()->assertJsonPath('data.0.account_id', $other->id);
    $this->postJson($this->world->url("/api/v1/students/{$other->id}/card"), ['cardUid' => '11:22:33:44'])
        ->assertOk()
        ->assertJsonPath('data.card_uid', '11223344');

    // A card already linked to someone else is refused.
    $this->postJson($this->world->url("/api/v1/students/{$other->id}/card"), ['cardUid' => '04A21B9C'])->assertUnprocessable();
});
