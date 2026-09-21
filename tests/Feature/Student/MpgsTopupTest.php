<?php

use App\Actions\Mpgs\InquireAction as MpgsInquireAction;
use App\Actions\QPay\RefundAction;
use App\Actions\QPay\ReleaseAction;
use App\Actions\QPay\StartPaymentAction;
use App\Actions\Student\GetBalanceAction;
use App\Livewire\Report\Student\QPayRechargeReport;
use App\Livewire\Settings\MpgsPayments;
use App\Models\ApiLog;
use App\Models\Configuration;
use App\Models\JournalEntry;
use App\Models\QpayTransaction;
use App\Services\Payment\MpgsClient;
use App\Support\Payment\MpgsSettings;
use App\Support\Payment\QPaySettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * Parents choose Debit card (QPay) or Credit card (Mastercard Gateway, Hosted
 * Checkout) when they top up. The card gateway is faked at the HTTP layer. What
 * these pin down: the parent sees each card type only when the school set it up;
 * a credit card payment is credited only when Retrieve Order says CAPTURED, exactly
 * once, into the credit card bank account; a declined card stays open for a retry
 * until the parent leaves; a cancelled one blocks nothing; and a refund goes back
 * through the same gateway.
 */
const MPGS_TEST_MERCHANT = 'TESTNEWREST01';
const MPGS_TEST_PASSWORD = 'mpgs-api-password-123';
const MPGS_TEST_HOST = 'https://test-cbq.mtf.gateway.mastercard.com';

beforeEach(function (): void {
    $this->world = PosWorld::create();
    StudentWorld::enableSchool($this->world);
    $this->debitBankId = $this->world->addPaymentMethod('QPay Bank');
    $this->creditBankId = $this->world->addPaymentMethod('CBQ Card Settlement');
    $this->student = StudentWorld::enrol($this->world);
    $this->guardian = $this->student->guardians->first();

    Configuration::create(['tenant_id' => $this->world->tenant->id, 'key' => QPaySettings::SECRET_KEY, 'value' => QPaySettings::encryptSecret('qpay-secret')]);
    Configuration::create(['tenant_id' => $this->world->tenant->id, 'key' => QPaySettings::KEY, 'value' => json_encode([
        'enabled' => true, 'environment' => 'staging', 'bank_id' => 'QIB', 'merchant_id' => 'MERCH01',
        'payment_account_id' => $this->debitBankId, 'user_id' => $this->world->user->id,
    ])]);
    mpgsConfigure($this);
    StudentWorld::setOverdraft($this->world, 0);

    // One fake for the whole test (Http::fake stubs stack, first match wins); tests swap the answer.
    $this->answer = fn () => Http::response(['result' => 'ERROR', 'error' => ['cause' => 'SERVER_FAILED', 'explanation' => 'No answer set up in this test.']], 500);
    Http::fake([MPGS_TEST_HOST.'/*' => fn (Request $request) => ($this->answer)($request)]);
});

/** What the fake gateway answers from now on. */
function mpgsAnswer($test, array $body, int $status = 200): void
{
    $test->answer = fn () => Http::response($body, $status);
}

function mpgsGrantSettings($test): void
{
    $test->world->user->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate([
        'tenant_id' => $test->world->tenant->id, 'name' => 'student settings.edit', 'guard_name' => 'web',
    ]));
    $test->actingAs($test->world->user);
}

function mpgsConfigure($test, array $overrides = []): void
{
    Configuration::updateOrCreate(['tenant_id' => $test->world->tenant->id, 'key' => MpgsSettings::PASSWORD_KEY], ['value' => MpgsSettings::encryptSecret(MPGS_TEST_PASSWORD)]);
    Configuration::updateOrCreate(['tenant_id' => $test->world->tenant->id, 'key' => MpgsSettings::KEY], ['value' => json_encode(array_merge([
        'enabled' => true, 'gateway_url' => MPGS_TEST_HOST, 'merchant_id' => MPGS_TEST_MERCHANT, 'merchant_name' => 'Newrest School Canteen',
        'payment_account_id' => $test->creditBankId, 'user_id' => $test->world->user->id,
    ], $overrides))]);
}

/** Retrieve Order for a paid (captured) order, as the gateway returns it. */
function mpgsOrder(QpayTransaction $transaction, array $overrides = []): array
{
    $amount = (float) $transaction->amount;

    return array_merge([
        'id' => $transaction->pun,
        'result' => 'SUCCESS',
        'status' => 'CAPTURED',
        'amount' => $amount,
        'currency' => 'QAR',
        'totalAuthorizedAmount' => $amount,
        'totalCapturedAmount' => $amount,
        'totalRefundedAmount' => 0,
        'authenticationStatus' => 'AUTHENTICATION_SUCCESSFUL',
        'sourceOfFunds' => ['type' => 'CARD', 'provided' => ['card' => ['brand' => 'VISA', 'scheme' => 'VISA', 'fundingMethod' => 'CREDIT', 'number' => '444000xxxxxx0010']]],
        'transaction' => [
            ['result' => 'SUCCESS', 'transaction' => ['id' => 'auth-1', 'type' => 'AUTHENTICATION'], 'response' => ['gatewayCode' => 'APPROVED']],
            ['result' => 'SUCCESS', 'transaction' => ['id' => '1', 'type' => 'PAYMENT', 'amount' => $amount, 'receipt' => '626512000123', 'authorizationCode' => '831000'], 'response' => ['gatewayCode' => 'APPROVED', 'acquirerCode' => '00']],
        ],
    ], $overrides);
}

function mpgsDeclined(QpayTransaction $transaction): array
{
    return mpgsOrder($transaction, [
        'result' => 'FAILURE',
        'status' => 'FAILED',
        'totalAuthorizedAmount' => 0,
        'totalCapturedAmount' => 0,
        'transaction' => [
            ['result' => 'FAILURE', 'transaction' => ['id' => '1', 'type' => 'PAYMENT', 'amount' => (float) $transaction->amount], 'response' => ['gatewayCode' => 'DECLINED', 'acquirerCode' => '05']],
        ],
    ]);
}

/** The gateway has no order: the parent never submitted a card. */
function mpgsUnknownOrder($test): void
{
    mpgsAnswer($test, ['result' => 'ERROR', 'error' => ['cause' => 'INVALID_REQUEST', 'explanation' => 'Unable to find order = X for merchant = '.MPGS_TEST_MERCHANT]], 400);
}

function mpgsStart($test, float $amount = 100): QpayTransaction
{
    $response = (new StartPaymentAction())->execute($test->guardian, $test->student, $amount, 'En', QpayTransaction::GATEWAY_MPGS);
    expect($response['success'])->toBeTrue($response['message']);

    return $response['data'];
}

function mpgsBack($test, QpayTransaction $transaction, string $query = '?resultIndicator=abc123&sessionVersion=1')
{
    return $test->get($test->world->url("/api/v1/parent/mpgs/return/{$transaction->pun}{$query}"));
}

function mpgsBalance($test): float
{
    return (new GetBalanceAction())->execute($test->student->id);
}

it('offers parents each card type only when the school has set it up', function (): void {
    $token = StudentWorld::parentToken($this->guardian);
    $show = fn () => $this->withToken($token)->getJson($this->world->url("/api/v1/parent/students/{$this->student->id}"))->assertOk();

    expect($show()->json('data.topup.methods.*.key'))->toBe(['debit', 'credit'])
        ->and($show()->json('data.topup.enabled'))->toBeTrue();

    mpgsConfigure($this, ['enabled' => false]);
    app('auth')->forgetGuards();
    expect($show()->json('data.topup.methods.*.key'))->toBe(['debit']);

    mpgsConfigure($this);
    Configuration::where('key', QPaySettings::KEY)->update(['value' => json_encode(['enabled' => false])]);
    app('auth')->forgetGuards();
    expect($show()->json('data.topup.methods.*.key'))->toBe(['credit'])
        ->and($show()->json('data.topup.methods.0.label'))->toBe('Credit card');
});

it('opens a credit card checkout session for the parent', function (): void {
    mpgsAnswer($this, ['result' => 'SUCCESS', 'session' => ['id' => 'SESSION0002345678901234567', 'updateStatus' => 'SUCCESS'], 'successIndicator' => 'f1e2d3c4b5a6'], 201);

    $response = $this->withToken(StudentWorld::parentToken($this->guardian))
        ->postJson($this->world->url("/api/v1/parent/students/{$this->student->id}/topups"), ['amount' => 150, 'method' => 'credit'])
        ->assertCreated();

    $transaction = QpayTransaction::sole();
    expect($transaction->gateway)->toBe('mpgs')
        ->and($transaction->status)->toBe('pending')
        ->and((float) $transaction->amount)->toBe(150.0)
        ->and($response->json('data.payment'))->toBe([
            'type' => 'mpgs',
            'script' => MPGS_TEST_HOST.'/static/checkout/checkout.min.js',
            'session_id' => 'SESSION0002345678901234567',
        ])
        ->and($response->json('data.topup.method'))->toBe('credit');

    Http::assertSent(function (Request $request) use ($transaction): bool {
        $body = $request->data();

        return $request->method() === 'POST'
            && $request->url() === MPGS_TEST_HOST.'/api/rest/version/100/merchant/'.MPGS_TEST_MERCHANT.'/session'
            && $request->header('Authorization')[0] === 'Basic '.base64_encode('merchant.'.MPGS_TEST_MERCHANT.':'.MPGS_TEST_PASSWORD)
            && $body['apiOperation'] === 'INITIATE_CHECKOUT'
            && $body['interaction']['operation'] === 'PURCHASE'
            && $body['interaction']['merchant']['name'] === 'Newrest School Canteen'
            && str_ends_with($body['interaction']['returnUrl'], "/api/v1/parent/mpgs/return/{$transaction->pun}")
            && str_ends_with($body['interaction']['cancelUrl'], "/api/v1/parent/mpgs/cancel/{$transaction->pun}")
            && $body['order'] === ['id' => $transaction->pun, 'reference' => $transaction->pun, 'amount' => '150.00', 'currency' => 'QAR', 'description' => 'Card top-up for '.$this->student->name];
    });

    // The API password travels only in the Authorization header, never into the log.
    $log = ApiLog::where('service_name', MpgsClient::LOG_CHECKOUT)->sole();
    expect($log->status)->toBe('success')
        ->and($log->username)->toBe(MPGS_TEST_MERCHANT)
        ->and((string) $log->request.(string) $log->response)->not->toContain(MPGS_TEST_PASSWORD);
});

it('closes the top-up at once when the gateway will not open the page', function (): void {
    mpgsAnswer($this, ['result' => 'ERROR', 'error' => ['cause' => 'INVALID_REQUEST', 'explanation' => 'Invalid credentials.']], 401);

    $this->withToken(StudentWorld::parentToken($this->guardian))
        ->postJson($this->world->url("/api/v1/parent/students/{$this->student->id}/topups"), ['amount' => 100, 'method' => 'credit'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'The card payment page could not be opened. Please try again, or pay by debit card.');

    expect(QpayTransaction::sole()->status)->toBe('failed')
        ->and(ApiLog::where('service_name', MpgsClient::LOG_CHECKOUT)->sole()->status)->toBe('failed');
});

it('credits the card exactly once when the gateway reports the order captured', function (): void {
    $transaction = mpgsStart($this, 100);
    mpgsAnswer($this, mpgsOrder($transaction));

    $response = mpgsBack($this, $transaction);
    expect($response->headers->get('Location'))->toBe(StudentWorld::PORTAL_URL."/#/topups/{$transaction->pun}");
    mpgsBack($this, $transaction);

    expect($transaction->refresh()->status)->toBe('success')
        ->and($transaction->card_brand)->toBe('VISA')
        ->and($transaction->funding_method)->toBe('CREDIT')
        ->and($transaction->masked_card)->toBe('444000xxxxxx0010')
        ->and($transaction->confirmation_id)->toBe('626512000123')
        ->and(mpgsBalance($this))->toBe(100.0)
        // Booked to the credit card bank account, not QPay's.
        ->and(JournalEntry::where('source', 'student_topup')->where('account_id', $this->creditBankId)->sum('debit'))->toEqual(100)
        ->and(JournalEntry::where('source', 'student_topup')->where('account_id', $this->debitBankId)->sum('debit'))->toEqual(0);

    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && $request->url() === MPGS_TEST_HOST.'/api/rest/version/100/merchant/'.MPGS_TEST_MERCHANT.'/order/'.$transaction->pun);

    $this->withToken(StudentWorld::parentToken($this->guardian))
        ->getJson($this->world->url("/api/v1/parent/topups/{$transaction->pun}"))
        ->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.method', 'credit')
        ->assertJsonPath('data.method_label', 'Credit card')
        ->assertJsonPath('data.card', 'Visa ····0010')
        ->assertJsonPath('data.balance', 100);
});

it('never believes the browser: an unpaid order stays unpaid whatever it brings back', function (): void {
    $transaction = mpgsStart($this, 100);
    mpgsUnknownOrder($this);

    mpgsBack($this, $transaction, '?resultIndicator=forged');

    expect($transaction->refresh()->status)->toBe('pending')
        ->and($transaction->gateway_status)->toBe(MpgsInquireAction::NOT_FOUND)
        ->and(mpgsBalance($this))->toBe(0.0);
});

it('keeps a declined card open for a retry, then fails it when the parent is sent back', function (): void {
    $transaction = mpgsStart($this, 100);
    mpgsAnswer($this, mpgsDeclined($transaction));

    (new MpgsInquireAction())->execute($transaction);
    expect($transaction->refresh()->status)->toBe('pending')
        ->and($transaction->gateway_status)->toBe('DECLINED');

    // Out of retries: the gateway sends the parent to redirectMerchantUrl (?final=1).
    mpgsBack($this, $transaction, '?final=1');

    expect($transaction->refresh()->status)->toBe('failed')
        ->and($transaction->gateway_status_message)->toBe('Your bank declined the payment.')
        ->and(mpgsBalance($this))->toBe(0.0);

    $this->withToken(StudentWorld::parentToken($this->guardian))
        ->getJson($this->world->url("/api/v1/parent/topups/{$transaction->pun}"))
        ->assertJsonPath('data.status', 'failed')
        ->assertJsonPath('data.message', 'Your bank declined the payment.');
});

it('marks a cancelled payment cancelled and lets the parent pay again at once', function (): void {
    $transaction = mpgsStart($this, 100);
    mpgsUnknownOrder($this);

    $response = $this->get($this->world->url("/api/v1/parent/mpgs/cancel/{$transaction->pun}"));

    expect($response->headers->get('Location'))->toBe(StudentWorld::PORTAL_URL."/#/topups/{$transaction->pun}")
        ->and($transaction->refresh()->status)->toBe('cancelled')
        ->and((new StartPaymentAction())->execute($this->guardian, $this->student, 50, 'En', QpayTransaction::GATEWAY_MPGS)['success'])->toBeTrue()
        ->and((new StartPaymentAction())->execute($this->guardian, $this->student, 50)['success'])->toBeTrue();
});

it('credits a cancelled payment that turns out to have been paid', function (): void {
    $transaction = mpgsStart($this, 60);
    mpgsUnknownOrder($this);
    $this->get($this->world->url("/api/v1/parent/mpgs/cancel/{$transaction->pun}"));
    expect($transaction->refresh()->status)->toBe('cancelled');

    mpgsAnswer($this, mpgsOrder($transaction));
    $this->travel(21)->minutes();
    $this->artisan('qpay:inquire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe('success')
        ->and(mpgsBalance($this))->toBe(60.0);
});

it('writes off a payment the gateway never received once the page has expired', function (): void {
    $transaction = mpgsStart($this, 100);
    mpgsUnknownOrder($this);

    $this->travel(21)->minutes();
    $this->artisan('qpay:inquire-pending')->assertSuccessful();
    expect($transaction->refresh()->status)->toBe('pending');

    $this->travel(MpgsInquireAction::ABANDONED_AFTER_MINUTES)->minutes();
    $this->artisan('qpay:inquire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe('failed')
        ->and($transaction->failure_reason)->toBe('No card payment was made.');
});

it('never lets a credit card payment block the card, while a QPay one still does', function (): void {
    mpgsStart($this, 100);
    expect((new StartPaymentAction())->execute($this->guardian, $this->student, 50)['success'])->toBeTrue();

    $blocked = (new StartPaymentAction())->execute($this->guardian, $this->student, 50, 'En', QpayTransaction::GATEWAY_MPGS);
    expect($blocked['success'])->toBeFalse()
        ->and($blocked['message'])->toContain('still being confirmed');
});

it('parks for review an order the gateway only authorised', function (): void {
    $transaction = mpgsStart($this, 100);
    mpgsAnswer($this, mpgsOrder($transaction, ['status' => 'AUTHORIZED', 'totalCapturedAmount' => 0]));

    mpgsBack($this, $transaction);

    expect($transaction->refresh()->status)->toBe('review')
        ->and($transaction->failure_reason)->toContain('AUTHORIZED')
        ->and(mpgsBalance($this))->toBe(0.0);
});

it('parks a captured amount that differs for review', function (): void {
    $transaction = mpgsStart($this, 100);
    mpgsAnswer($this, mpgsOrder($transaction, ['totalCapturedAmount' => 90.0]));

    mpgsBack($this, $transaction);

    expect($transaction->refresh()->status)->toBe('review')
        ->and($transaction->failure_reason)->toBe('MPGS reports 90 paid for a top-up of 100.00.')
        ->and(mpgsBalance($this))->toBe(0.0);
});

it('refunds a credit card top-up through the gateway and takes it off the card', function (): void {
    $transaction = mpgsStart($this, 100);
    mpgsAnswer($this, mpgsOrder($transaction));
    mpgsBack($this, $transaction);
    expect(mpgsBalance($this))->toBe(100.0);

    mpgsAnswer($this, [
        'result' => 'SUCCESS',
        'order' => ['status' => 'REFUNDED', 'totalRefundedAmount' => 100.0],
        'transaction' => ['id' => 'R1', 'type' => 'REFUND', 'receipt' => '626512000999'],
        'response' => ['gatewayCode' => 'APPROVED'],
    ], 201);

    $response = (new RefundAction())->execute($transaction->id, $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        ->and($response['data']->gateway)->toBe('mpgs')
        ->and($response['data']->status)->toBe('success')
        ->and($response['data']->confirmation_id)->toBe('626512000999')
        ->and($transaction->refresh()->status)->toBe('refunded')
        ->and(mpgsBalance($this))->toBe(0.0)
        ->and(JournalEntry::where('source', 'student_topup_refund')->where('account_id', $this->creditBankId)->sum('credit'))->toEqual(100);

    $refund = $response['data'];
    Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
        && $request->url() === MPGS_TEST_HOST.'/api/rest/version/100/merchant/'.MPGS_TEST_MERCHANT."/order/{$transaction->pun}/transaction/{$refund->pun}"
        && $request->data() === ['apiOperation' => 'REFUND', 'transaction' => ['amount' => '100.00', 'currency' => 'QAR']]);
});

it('records a refund the gateway declined without touching the card', function (): void {
    $transaction = mpgsStart($this, 100);
    mpgsAnswer($this, mpgsOrder($transaction));
    mpgsBack($this, $transaction);

    mpgsAnswer($this, ['result' => 'FAILURE', 'response' => ['gatewayCode' => 'DECLINED', 'acquirerMessage' => 'Refund not allowed']], 201);

    $response = (new RefundAction())->execute($transaction->id, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toBe('MPGS refused the refund: Refund not allowed')
        ->and($transaction->refresh()->status)->toBe('success')
        ->and(mpgsBalance($this))->toBe(100.0);
});

it('has nothing to release for a credit card payment', function (): void {
    $transaction = mpgsStart($this, 100);
    $this->travel(21)->minutes();

    $response = (new ReleaseAction())->execute($transaction, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('never blocks the card');
    Http::assertNothingSent();
});

it('saves the credit card settings apart from QPay, with the password encrypted', function (): void {
    mpgsGrantSettings($this);

    Livewire::test(MpgsPayments::class)
        ->set('enabled', true)
        ->set('gateway_url', 'http://test-cbq.mtf.gateway.mastercard.com/some/path')
        ->set('merchant_id', 'TESTABC')
        ->set('api_password', 'new-password-9876')
        ->set('payment_account_id', (string) $this->creditBankId)
        ->set('user_id', (string) $this->world->user->id)
        ->call('save')
        ->assertDispatched('error');

    Livewire::test(MpgsPayments::class)
        ->set('enabled', true)
        ->set('gateway_url', 'https://TEST-CBQ.mtf.gateway.mastercard.com/api/')
        ->set('merchant_id', 'TESTABC')
        ->set('merchant_name', 'School Canteen')
        ->set('api_password', 'new-password-9876')
        ->set('payment_account_id', (string) $this->creditBankId)
        ->set('user_id', (string) $this->world->user->id)
        ->call('save')
        ->assertDispatched('success')
        ->assertSet('api_password', '')
        ->assertSet('saved_password_hint', '…9876');

    $settings = MpgsSettings::current();
    expect($settings->isReady())->toBeTrue()
        ->and($settings->gatewayUrl)->toBe(MPGS_TEST_HOST)
        ->and($settings->merchantId)->toBe('TESTABC')
        ->and($settings->apiPassword)->toBe('new-password-9876')
        ->and($settings->isTest())->toBeTrue()
        ->and(Configuration::where('key', MpgsSettings::PASSWORD_KEY)->value('value'))->not->toContain('new-password-9876')
        // QPay's block is untouched.
        ->and(QPaySettings::current()->merchantId)->toBe('MERCH01');
});

it('tests the saved gateway credentials without creating anything', function (): void {
    mpgsGrantSettings($this);

    mpgsUnknownOrder($this);
    Livewire::test(MpgsPayments::class)->call('testConnection')->assertDispatched('success');
    Http::assertSent(fn (Request $request) => $request->method() === 'GET' && str_contains($request->url(), '/order/CONNTEST'));

    mpgsAnswer($this, ['result' => 'ERROR', 'error' => ['cause' => 'INVALID_REQUEST', 'explanation' => 'Invalid credentials.']], 401);
    Livewire::test(MpgsPayments::class)->call('testConnection')->assertDispatched('error');
});

it('tells debit and credit card recharges apart in the report', function (): void {
    $this->actingAs($this->world->user);
    $credit = mpgsStart($this, 100);
    QpayTransaction::create([
        'type' => 'payment', 'gateway' => 'qpay', 'pun' => 'PUNDEBIT000000000001', 'account_id' => $this->student->id,
        'amount' => 40, 'status' => 'pending', 'currency_code' => '634',
    ]);

    expect(Livewire::test(QPayRechargeReport::class)->viewData('rows')->count())->toBe(2)
        ->and(Livewire::test(QPayRechargeReport::class)->set('gateway', 'mpgs')->viewData('rows')->pluck('pun')->all())->toBe([$credit->pun])
        ->and(Livewire::test(QPayRechargeReport::class)->set('gateway', 'qpay')->viewData('rows')->pluck('pun')->all())->toBe(['PUNDEBIT000000000001']);
});

it('starts "Record top-ups as" from the QPay user and names the field that is missing', function (): void {
    mpgsGrantSettings($this);
    Configuration::where('key', MpgsSettings::KEY)->delete();

    Livewire::test(MpgsPayments::class)
        ->assertSet('user_id', (string) $this->world->user->id)
        ->set('user_id', '')
        ->set('enabled', true)
        ->set('merchant_id', 'TESTABC')
        ->set('payment_account_id', (string) $this->creditBankId)
        ->call('save')
        ->assertDispatched('error', fn ($name, $params) => $params[0]['message'] === 'Choose a user under "Record top-ups as" to switch on credit card top-ups.');
});
