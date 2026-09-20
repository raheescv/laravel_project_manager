<?php

use App\Actions\QPay\InquireAction;
use App\Actions\QPay\RefundAction;
use App\Actions\QPay\ReleaseAction;
use App\Actions\QPay\StartPaymentAction;
use App\Actions\Student\GetBalanceAction;
use App\Console\Commands\Student\QPayInquirePendingCommand;
use App\Models\ApiLog;
use App\Models\Configuration;
use App\Models\JournalEntry;
use App\Models\QpayTransaction;
use App\Services\Payment\QPayApiLog;
use App\Services\Payment\QPayClient;
use App\Support\Payment\QPaySettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * Parents top up a student card through QPay (QCB EZ-Connect). QPay is faked at
 * the HTTP layer. What these pin down: money reaches the card only from a signed
 * (or inquired) result, exactly once; a tampered result is never believed; a
 * payment without a result blocks paying twice; and a refund takes the money back.
 */
const QPAY_TEST_SECRET = 'test-secret-key-123';

beforeEach(function (): void {
    $this->world = PosWorld::create();
    StudentWorld::enableSchool($this->world);
    $this->bankAccountId = $this->world->addPaymentMethod('QPay Bank');
    $this->student = StudentWorld::enrol($this->world);
    $this->guardian = $this->student->guardians->first();

    Configuration::create(['tenant_id' => $this->world->tenant->id, 'key' => QPaySettings::SECRET_KEY, 'value' => QPaySettings::encryptSecret(QPAY_TEST_SECRET)]);
    Configuration::create(['tenant_id' => $this->world->tenant->id, 'key' => QPaySettings::KEY, 'value' => json_encode([
        'enabled' => true, 'environment' => 'staging', 'bank_id' => 'QIB', 'merchant_id' => 'MERCH01',
        'payment_account_id' => $this->bankAccountId, 'user_id' => $this->world->user->id,
    ])]);
    StudentWorld::setOverdraft($this->world, 0);
});

/** A urlencoded QPay response, signed the way the guide signs it (or with [$hash]). */
function qpaySigned(array $values, ?string $hash = null): string
{
    $hash ??= QPayClient::responseHashes(QPAY_TEST_SECRET, $values)[0];
    $fields = [];
    foreach ($values as $name => $value) {
        $fields['Response.'.$name] = $value;
    }
    $fields['Response.SecureHash'] = $hash;

    return http_build_query($fields);
}

function qpayPaymentResponse(QpayTransaction $transaction, array $overrides = []): array
{
    return array_merge([
        'AcquirerID' => '030003',
        'Amount' => QPayClient::minorUnits($transaction->amount),
        'BankID' => 'QIB',
        'CardExpiryDate' => '3006',
        'CardHolderName' => 'NOT_CAPTURED',
        'CardNumber' => '421537******3243',
        'ConfirmationID' => '202609170113174499433083352',
        'CurrencyCode' => '634',
        'EZConnectResponseDate' => '17092026161626',
        'Lang' => 'En',
        'MerchantID' => 'MERCH01',
        'MerchantModuleSessionID' => $transaction->pun,
        'PUN' => $transaction->pun,
        'Status' => '0000',
        'StatusMessage' => 'Payment Processed Successfully',
    ], $overrides);
}

function qpayInquiryResponse(QpayTransaction $transaction, array $overrides = []): string
{
    return qpaySigned(array_merge([
        'Status' => '0000',
        'StatusMessage' => 'Inquiry Successful',
        'OriginalStatus' => '0000',
        'OriginalStatusMessage' => 'Payment processed successfully.',
        'OriginalConfirmationID' => '202609170113174499433083352',
        'OriginalPUN' => $transaction->pun,
        'Amount' => QPayClient::minorUnits($transaction->amount),
        'CurrencyCode' => '634',
        'MerchantID' => 'MERCH01',
        'BankID' => 'QIB',
        'CardNumber' => '421537******3243',
        'EZConnectResponseDate' => '17092026162000',
        'TransactionResponseDate' => '17092026161626',
        'Quantity' => '1',
    ], $overrides));
}

function qpayStart($test, float $amount = 100): QpayTransaction
{
    $response = (new StartPaymentAction())->execute($test->guardian, $test->student, $amount);
    expect($response['success'])->toBeTrue($response['message']);

    return $response['data'];
}

function qpayPostReturn($test, string $body)
{
    return $test->call('POST', $test->world->url('/api/v1/parent/qpay/return'), [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], $body);
}

function qpayBalance($test): float
{
    return (new GetBalanceAction())->execute($test->student->id);
}

it('hands the portal a signed QPay form in Qatar time', function (): void {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-17 10:00:00', 'UTC'));

    $response = $this->withToken(StudentWorld::parentToken($this->guardian))
        ->postJson($this->world->url("/api/v1/parent/students/{$this->student->id}/topups"), ['amount' => 150])
        ->assertCreated();

    $transaction = QpayTransaction::sole();
    expect((float) $transaction->amount)->toBe(150.0)
        ->and($transaction->request_date)->toBe('17092026130000');

    $fields = $response->json('data.payment.fields');
    expect($response->json('data.payment.url'))->toBe('https://pguat.qcb.gov.qa/qcb-pg/api/gateway/2.0')
        ->and($response->json('data.topup.pun'))->toBe($transaction->pun)
        ->and($response->json('data.topup.status'))->toBe('pending')
        ->and($fields['Amount'])->toBe('15000')
        ->and($fields['PUN'])->toBe($transaction->pun)
        ->and($fields['ExtraFields_f14'])->toEndWith('/api/v1/parent/qpay/return')
        ->and($fields['SecureHash'])->toBe(QPayClient::hash(QPAY_TEST_SECRET, $fields));
});

it('explains a top-up the school cannot take', function (): void {
    $token = StudentWorld::parentToken($this->guardian);

    $this->withToken($token)
        ->postJson($this->world->url("/api/v1/parent/students/{$this->student->id}/topups"), ['amount' => 5000])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    config(['services.parent_portal.url' => null]);
    app('auth')->forgetGuards();
    $this->withToken($token)
        ->postJson($this->world->url("/api/v1/parent/students/{$this->student->id}/topups"), ['amount' => 100])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Online top-up is not available right now. Please contact the school office.');

    expect(QpayTransaction::count())->toBe(0);
});

it('credits the card exactly once for a signed successful result', function (): void {
    $transaction = qpayStart($this, 100);
    $body = qpaySigned(qpayPaymentResponse($transaction));

    $response = qpayPostReturn($this, $body);
    expect($response->headers->get('Location'))->toBe(StudentWorld::PORTAL_URL."/#/topups/{$transaction->pun}");

    qpayPostReturn($this, $body);

    expect($transaction->refresh()->status)->toBe('success')
        ->and($transaction->confirmation_id)->toBe('202609170113174499433083352')
        ->and(qpayBalance($this))->toBe(100.0)
        ->and(JournalEntry::where('source', 'student_topup')->where('account_id', $this->bankAccountId)->sum('debit'))->toEqual(100);

    $this->withToken(StudentWorld::parentToken($this->guardian))
        ->getJson($this->world->url("/api/v1/parent/topups/{$transaction->pun}"))
        ->assertOk()
        ->assertJsonPath('data.pun', $transaction->pun)
        ->assertJsonPath('data.amount', 100)
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.status_label', 'Successful')
        ->assertJsonPath('data.balance', 100);
});

it('sends a result it cannot match back to the portal home', function (): void {
    $response = qpayPostReturn($this, qpaySigned(['PUN' => 'NOTAPAYMENT000000000', 'Status' => '0000']));

    expect($response->headers->get('Location'))->toBe(StudentWorld::PORTAL_URL.'/#/?payment=unmatched');
});

it('shows a parent only their own children\'s top-ups', function (): void {
    $transaction = qpayStart($this, 100);
    $other = StudentWorld::enrol($this->world, [
        'name' => 'Other Child',
        'card_uid' => '0A0B0C0D',
        'guardians' => [['name' => 'Other Parent', 'mobile' => '66000000', 'relation' => 'mother']],
    ]);

    $this->withToken(StudentWorld::parentToken($other->guardians->first()))
        ->getJson($this->world->url("/api/v1/parent/topups/{$transaction->pun}"))
        ->assertNotFound();
});

it('records a declined payment as failed without touching the card', function (): void {
    $transaction = qpayStart($this, 100);

    qpayPostReturn($this, qpaySigned(qpayPaymentResponse($transaction, ['Status' => '3000', 'StatusMessage' => 'Payment Failed.'])));

    expect($transaction->refresh()->status)->toBe('failed')
        ->and(qpayBalance($this))->toBe(0.0);
});

it('drops a tampered result and believes only the inquiry', function (): void {
    $transaction = qpayStart($this, 100);
    Http::fake(fn () => Http::response(qpayInquiryResponse($transaction, ['OriginalStatus' => '3000', 'OriginalStatusMessage' => 'Payment Failed.'])));

    // Claims success, but the hash is not QPay's.
    qpayPostReturn($this, qpaySigned(qpayPaymentResponse($transaction), str_repeat('a', 64)));

    Http::assertSent(fn (Request $request) => $request['Action'] === '14' && $request['OriginalPUN'] === $transaction->pun
        && $request['SecureHash'] === QPayClient::hash(QPAY_TEST_SECRET, ['Action' => '14', 'BankID' => 'QIB', 'Lang' => 'En', 'MerchantID' => 'MERCH01', 'OriginalPUN' => $transaction->pun]));

    expect($transaction->refresh()->tampered_at)->not->toBeNull()
        ->and($transaction->status)->toBe('failed')
        ->and(qpayBalance($this))->toBe(0.0);
});

it('refuses an inquiry answer that is not signed by QPay', function (): void {
    $transaction = qpayStart($this, 100);
    Http::fake(fn () => Http::response(qpaySigned(['Status' => '0000', 'OriginalStatus' => '0000', 'OriginalPUN' => $transaction->pun, 'Amount' => '10000'], str_repeat('b', 64))));
    $this->travel(21)->minutes();

    $this->artisan('qpay:inquire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe('pending')
        ->and(qpayBalance($this))->toBe(0.0);
});

it('parks a payment whose captured amount differs for review', function (): void {
    $transaction = qpayStart($this, 100);

    qpayPostReturn($this, qpaySigned(qpayPaymentResponse($transaction, ['Amount' => '1000'])));

    expect($transaction->refresh()->status)->toBe('review')
        ->and(qpayBalance($this))->toBe(0.0);
});

it('blocks a second top-up while the first has no result, then inquires it after 20 minutes', function (): void {
    $first = qpayStart($this, 100);

    // A minute past the inquiry cutoff: the portal counts the parent down to it.
    $retryAt = $first->created_at->copy()->addMinutes(StartPaymentAction::BROKEN_AFTER_MINUTES + 1);

    $blocked = (new StartPaymentAction())->execute($this->guardian, $this->student, 50);
    expect($blocked['success'])->toBeFalse()
        ->and($blocked['message'])->toContain('still being confirmed')
        ->and($blocked['retry_at'])->toBe($retryAt->toIso8601String());

    $this->withToken(StudentWorld::parentToken($this->guardian))
        ->postJson($this->world->url("/api/v1/parent/students/{$this->student->id}/topups"), ['amount' => 50])
        ->assertStatus(422)
        ->assertJsonPath('data.retry_at', $retryAt->toIso8601String());

    Http::fake(fn () => Http::response(qpaySigned(['Status' => QPayClient::NOT_FOUND, 'StatusMessage' => 'Try to Inquiry about unfounded transaction'])));
    $this->travel(22)->minutes();

    $second = (new StartPaymentAction())->execute($this->guardian, $this->student, 50);

    expect($second['success'])->toBeTrue($second['message'])
        ->and($first->refresh()->status)->toBe('failed');
});

it('settles a broken transaction from the scheduled inquiry', function (): void {
    $transaction = qpayStart($this, 80);
    Http::fake(fn () => Http::response(qpayInquiryResponse($transaction)));

    $this->artisan('qpay:inquire-pending')->assertSuccessful();
    expect($transaction->refresh()->status)->toBe('pending');
    Http::assertNothingSent();

    $this->travel(21)->minutes();
    $this->artisan('qpay:inquire-pending')->assertSuccessful();

    expect($transaction->refresh()->status)->toBe('success')
        ->and(qpayBalance($this))->toBe(80.0);
});

/** QPay answers, verifiably, but says nothing that settles the payment either way. */
function qpayAnswersNothing(): void
{
    Http::fake(fn () => Http::response(qpaySigned(['Status' => '1220', 'StatusMessage' => 'Transaction is in progress'])));
}

it('counts the parent down instead of leaving them tapping an unanswerable block', function (): void {
    qpayStart($this, 100);
    $this->travel(22)->minutes();
    qpayAnswersNothing();

    $blocked = (new StartPaymentAction())->execute($this->guardian, $this->student, 50);

    // The old behaviour threw a bare message with no retry_at, so the portal left
    // the Pay button live and the parent looped on the same sentence for ever.
    expect($blocked['success'])->toBeFalse()
        ->and($blocked['message'])->toContain('the school office can release it')
        ->and($blocked['retry_at'])->toBe(now()->addMinutes(StartPaymentAction::RETRY_AFTER_INQUIRY_MINUTES)->toIso8601String());

    $this->withToken(StudentWorld::parentToken($this->guardian))
        ->postJson($this->world->url("/api/v1/parent/students/{$this->student->id}/topups"), ['amount' => 50])
        ->assertStatus(422)
        ->assertJsonPath('data.retry_at', now()->addMinutes(StartPaymentAction::RETRY_AFTER_INQUIRY_MINUTES)->toIso8601String());
});

it('stops a payment QPay never answers for from blocking the card for ever', function (): void {
    $stuck = qpayStart($this, 100);
    qpayAnswersNothing();

    $this->travel(22)->minutes();
    expect((new StartPaymentAction())->execute($this->guardian, $this->student, 50)['success'])->toBeFalse();

    $this->travel(StartPaymentAction::BLOCK_EXPIRES_AFTER_HOURS)->hours();
    $freed = (new StartPaymentAction())->execute($this->guardian, $this->student, 50);

    // Freed, but nothing was decided about the money: the row is still pending and
    // still being chased, because we never learned what happened to it.
    expect($freed['success'])->toBeTrue($freed['message'])
        ->and($stuck->refresh()->status)->toBe('pending');
});

it('releases a payment QPay will not answer for so the parent can pay again', function (): void {
    $stuck = qpayStart($this, 100);
    $this->travel(21)->minutes();
    qpayAnswersNothing();

    $response = (new ReleaseAction())->execute($stuck, $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        // Never "failed" — failed means QPay confirmed no money was taken.
        ->and($stuck->refresh()->status)->toBe('unresolved')
        ->and($stuck->failure_reason)->toContain('Released by')
        ->and(qpayBalance($this))->toBe(0.0);

    expect((new StartPaymentAction())->execute($this->guardian, $this->student, 50)['success'])->toBeTrue();
});

it('settles a payment rather than releasing it when QPay finally answers', function (): void {
    $stuck = qpayStart($this, 100);
    $this->travel(21)->minutes();
    Http::fake(fn () => Http::response(qpayInquiryResponse($stuck)));

    $response = (new ReleaseAction())->execute($stuck, $this->world->user->id);

    expect($response['success'])->toBeTrue()
        ->and($response['message'])->toContain('Nothing needed releasing')
        ->and($stuck->refresh()->status)->toBe('success')
        ->and(qpayBalance($this))->toBe(100.0);
});

it('will not release a payment the parent may still be making', function (): void {
    $fresh = qpayStart($this, 100);

    $response = (new ReleaseAction())->execute($fresh, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('may still be in progress')
        ->and($fresh->refresh()->status)->toBe('pending');
    Http::assertNothingSent();
});

it('credits a released payment that turns out to have been paid', function (): void {
    $stuck = qpayStart($this, 100);

    // One stub for the whole test: a second Http::fake() would never be reached,
    // because the first registered closure answers every request.
    $paid = false;
    Http::fake(function () use (&$paid, $stuck) {
        return Http::response($paid
            ? qpayInquiryResponse($stuck)
            : qpaySigned(['Status' => '1220', 'StatusMessage' => 'Transaction is in progress']));
    });

    $this->travel(21)->minutes();
    (new ReleaseAction())->execute($stuck, $this->world->user->id);
    expect($stuck->refresh()->status)->toBe('unresolved');

    // Releasing only freed the card. The scheduled chase goes on, and QPay comes
    // back later to say the money was taken after all.
    $paid = true;
    $this->travel(21)->minutes();
    $this->artisan('qpay:inquire-pending')->assertSuccessful();

    expect($stuck->refresh()->status)->toBe('success')
        ->and(qpayBalance($this))->toBe(100.0);
});

it('gives up chasing a payment no answer ever came for', function (): void {
    $stuck = qpayStart($this, 100);
    $this->travel(QPayInquirePendingCommand::CHASE_FOR_DAYS + 1)->days();
    Http::fake(fn () => Http::response(qpayInquiryResponse($stuck)));

    $this->artisan('qpay:inquire-pending')->assertSuccessful();

    Http::assertNothingSent();
    expect($stuck->refresh()->status)->toBe('pending');
});

it('refunds a top-up in full and takes it off the card', function (): void {
    $transaction = qpayStart($this, 100);
    qpayPostReturn($this, qpaySigned(qpayPaymentResponse($transaction)));
    expect(qpayBalance($this))->toBe(100.0);

    Http::fake(fn (Request $request) => Http::response(qpaySigned([
        'Amount_1' => '10000', 'ConfirmationID_1' => '', 'CurrencyCode_1' => '634', 'EZConnectRequestStatus' => '',
        'EZConnectResponseDate' => '17092026162003', 'Lang_1' => 'En', 'OriginalTransactionPaymentUniqueNumber_1' => $transaction->pun,
        'PUN_1' => $request['PUN_1'], 'StatusMessage_1' => 'Refund Transaction is pending', 'Status_1' => '5002',
        'TransactionRequestDate_1' => '17/09/2026 16:19:32',
    ])));

    $response = (new RefundAction())->execute($transaction->id, $this->world->user->id);

    expect($response['success'])->toBeTrue($response['message'])
        ->and($response['data']->status)->toBe('refund_pending')
        ->and($transaction->refresh()->status)->toBe('refunded')
        ->and(qpayBalance($this))->toBe(0.0);

    expect((new RefundAction())->execute($transaction->id, $this->world->user->id)['success'])->toBeFalse();
});

it('will not refund a top-up the student has already spent', function (): void {
    $transaction = qpayStart($this, 100);
    qpayPostReturn($this, qpaySigned(qpayPaymentResponse($transaction)));
    (new \App\Actions\Sale\CreateAction())->execute(StudentWorld::salePayload($this->world, $this->student->id, 30, card: 30), $this->world->user->id);
    Http::fake();

    $response = (new RefundAction())->execute($transaction->id, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('must still hold it');
    Http::assertNothingSent();
});

it('rejects a top-up outside the school\'s limits', function (): void {
    $response = (new StartPaymentAction())->execute($this->guardian, $this->student, 5000);

    expect($response['success'])->toBeFalse()
        ->and(QpayTransaction::count())->toBe(0);
});

it('logs the payment form and closes the same row with QPay\'s result', function (): void {
    $this->withToken(StudentWorld::parentToken($this->guardian))
        ->postJson($this->world->url("/api/v1/parent/students/{$this->student->id}/topups"), ['amount' => 100])
        ->assertCreated();
    $transaction = QpayTransaction::sole();

    $log = ApiLog::where('service_name', QPayApiLog::PAYMENT)->sole();
    $sent = json_decode($log->request, true);
    expect($log->status)->toBe('pending')
        ->and($log->endpoint)->toBe('https://pguat.qcb.gov.qa/qcb-pg/api/gateway/2.0')
        ->and($log->username)->toBe('MERCH01')
        ->and($log->user_id)->toBeNull()
        ->and($log->user_name)->toBe($this->guardian->name)
        ->and($sent['PUN'])->toBe($transaction->pun)
        ->and($sent['SecureHash'])->toBe(QPayClient::hash(QPAY_TEST_SECRET, $sent))
        ->and($log->request)->not->toContain(QPAY_TEST_SECRET);

    $body = qpaySigned(qpayPaymentResponse($transaction));
    qpayPostReturn($this, $body);

    expect($log->refresh()->status)->toBe('success')
        ->and($log->description)->toBeNull()
        ->and(json_decode($log->response, true))->toMatchArray(['PUN' => $transaction->pun, 'Status' => '0000', 'SecureHash' => QPayClient::parseResponse($body)['hash']]);

    // A repeat post has no open payment row left to close, so it gets its own.
    qpayPostReturn($this, $body);
    expect(ApiLog::where('service_name', QPayApiLog::RETURN)->sole()->status)->toBe('success')
        ->and(ApiLog::count())->toBe(2);
});

it('logs a declined payment with QPay\'s code', function (): void {
    $transaction = qpayStart($this, 100);

    qpayPostReturn($this, qpaySigned(qpayPaymentResponse($transaction, ['Status' => 'EZConnect-0008', 'StatusMessage' => 'Merchant IP is not supported'])));

    $log = ApiLog::where('service_name', QPayApiLog::RETURN)->sole();
    expect($log->status)->toBe('failed')
        ->and($log->description)->toBe('EZConnect-0008: Merchant IP is not supported')
        ->and($transaction->refresh()->status)->toBe('failed');
});

it('logs a result it cannot match, or cannot verify, as failed', function (): void {
    qpayPostReturn($this, qpaySigned(['PUN' => 'NOTAPAYMENT000000000', 'Status' => '0000']));

    expect(ApiLog::where('service_name', QPayApiLog::RETURN)->sole())
        ->status->toBe('failed')
        ->description->toBe('No top-up has this PUN.');

    $transaction = qpayStart($this, 100);
    Http::fake(fn () => Http::response(qpayInquiryResponse($transaction)));
    qpayPostReturn($this, qpaySigned(qpayPaymentResponse($transaction), str_repeat('a', 64)));

    expect(ApiLog::where('service_name', QPayApiLog::RETURN)->latest('id')->first())
        ->status->toBe('failed')
        ->description->toContain('secure hash check');
});

it('logs every inquiry with what was sent and what QPay answered', function (): void {
    $transaction = qpayStart($this, 100);
    Http::fake(fn () => Http::response(qpayInquiryResponse($transaction)));
    $this->travel(21)->minutes();

    $this->artisan('qpay:inquire-pending')->assertSuccessful();

    $log = ApiLog::where('service_name', QPayApiLog::INQUIRY)->sole();
    expect($log->status)->toBe('success')
        ->and($log->user_name)->toBeNull()
        ->and(json_decode($log->request, true))->toMatchArray(['Action' => '14', 'OriginalPUN' => $transaction->pun, 'MerchantID' => 'MERCH01'])
        ->and(json_decode($log->response, true))->toMatchArray(['OriginalStatus' => '0000', 'OriginalPUN' => $transaction->pun]);
});

it('logs an inquiry QPay could not be reached for', function (): void {
    $transaction = qpayStart($this, 100);
    Http::fake(['*' => Http::failedConnection('cURL error 28: Operation timed out')]);

    expect((new InquireAction())->execute($transaction)['success'])->toBeFalse();

    expect(ApiLog::where('service_name', QPayApiLog::INQUIRY)->sole())
        ->status->toBe('failed')
        ->response->toBeNull()
        ->description->toContain('Operation timed out');
});

it('logs a refund QPay refused, under the staff member who asked', function (): void {
    $transaction = qpayStart($this, 100);
    qpayPostReturn($this, qpaySigned(qpayPaymentResponse($transaction)));
    Http::fake(fn () => Http::response(qpaySigned([
        'EZConnectRequestStatus' => 'EZConnect-0008', 'EZConnectStatusMessage' => 'Merchant IP is not supported', 'EZConnectResponseDate' => '17092026162003',
    ])));
    $this->actingAs($this->world->user);

    $response = (new RefundAction())->execute($transaction->id, $this->world->user->id);

    $log = ApiLog::where('service_name', QPayApiLog::REFUND)->sole();
    expect($response['success'])->toBeFalse()
        ->and($log->status)->toBe('failed')
        ->and($log->description)->toBe('EZConnect-0008: Merchant IP is not supported')
        ->and($log->user_id)->toBe($this->world->user->id)
        ->and(json_decode($log->request, true))->toMatchArray(['Action' => '6', 'OriginalTransactionPaymentUniqueNumber_1' => $transaction->pun]);
});
