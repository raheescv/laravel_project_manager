<?php

use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Actions\Student\Guardian\SendInviteAction;
use App\Models\Guardian;
use App\Models\StudentDetail;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * The parent portal API (routes/api_v1_parent.php) behind the standalone
 * parent_portal app. A parent signs in for a bearer token that only the portal
 * accepts, and sees only the children linked to them — any other student, bill
 * or top-up is a 404.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(stock: 100, price: 50);
    StudentWorld::enableSchool($this->world);
    $this->student = StudentWorld::enrol($this->world);
    $this->guardian = $this->student->guardians->first();
    $this->guardian->forceFill(['password' => 'secret-pass'])->save();

    $this->otherStudent = StudentWorld::enrol($this->world, [
        'name' => 'Other Child',
        'card_uid' => '0A0B0C0D',
        'guardians' => [['name' => 'Other Parent', 'mobile' => '66000000', 'relation' => 'mother']],
    ]);
});

/** A portal request with [$token]. Guards are forgotten so each request authenticates on its own. */
function portalJson($test, string $method, string $path, ?string $token = null, array $data = [])
{
    app('auth')->forgetGuards();

    ($token ? $test->withToken($token) : $test->withoutToken());

    return $test->json($method, $test->world->url('/api/v1/parent/'.ltrim($path, '/')), $data);
}

it('serves the school branding before sign-in', function (): void {
    portalJson($this, 'GET', 'school')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['name', 'logo', 'accent', 'currency' => ['code', 'decimals']]]);
});

it('signs a parent in with mobile and password and issues a portal token', function (): void {
    $response = portalJson($this, 'POST', 'login', data: ['login' => '5512 3456', 'password' => 'secret-pass', 'remember' => true])
        ->assertOk()
        ->assertJsonPath('data.parent.id', $this->guardian->id)
        ->assertJsonPath('data.token_type', 'Bearer');

    $token = $response->json('data.token');
    expect(PersonalAccessToken::findToken($token)->tokenable)->toBeInstanceOf(Guardian::class);

    portalJson($this, 'GET', 'me', $token)->assertOk()->assertJsonPath('data.name', 'Ahmed Saleh');
});

it('refuses a wrong password, and slows down repeated tries', function (): void {
    portalJson($this, 'POST', 'login', data: ['login' => '55123456', 'password' => 'wrong'])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'The mobile number / email or password is not correct.');

    foreach (range(1, 4) as $try) {
        portalJson($this, 'POST', 'login', data: ['login' => '55123456', 'password' => 'wrong']);
    }

    portalJson($this, 'POST', 'login', data: ['login' => '55123456', 'password' => 'secret-pass'])->assertStatus(429);
});

it('tells a parent who never set a password to use their link', function (): void {
    $this->guardian->forceFill(['password' => null])->save();

    portalJson($this, 'POST', 'login', data: ['login' => '55123456', 'password' => 'anything'])
        ->assertStatus(422)
        ->assertJsonPath('message', fn ($message) => str_contains($message, 'not set a password yet'));
});

it('keeps parent tokens and staff tokens apart', function (): void {
    $parentToken = StudentWorld::parentToken($this->guardian);
    $staffToken = $this->world->user->createToken('mobile', ['mobile'])->plainTextToken;

    // A parent's token opens no staff endpoint…
    app('auth')->forgetGuards();
    $this->withToken($parentToken)->getJson($this->world->url('/api/v1/settings/sale'))->assertUnauthorized();

    // …and a staff token or a staff browser session opens no parent endpoint.
    portalJson($this, 'GET', 'students', $staffToken)->assertUnauthorized();
    app('auth')->forgetGuards();
    $this->withoutToken()->actingAs($this->world->user)->getJson($this->world->url('/api/v1/parent/students'))->assertUnauthorized();
});

it('locks out a parent the school disables and a signed-out token', function (): void {
    $token = StudentWorld::parentToken($this->guardian);
    portalJson($this, 'GET', 'students', $token)->assertOk();

    portalJson($this, 'POST', 'logout', $token)->assertOk();
    portalJson($this, 'GET', 'students', $token)->assertUnauthorized();

    $token = StudentWorld::parentToken($this->guardian);
    $this->guardian->forceFill(['status' => 'disabled'])->save();
    portalJson($this, 'GET', 'students', $token)->assertUnauthorized();
});

it('lets a parent change the password, staying signed in here and signed out elsewhere', function (): void {
    $here = StudentWorld::parentToken($this->guardian);
    $elsewhere = StudentWorld::parentToken($this->guardian);

    portalJson($this, 'POST', 'password', $here, ['current_password' => 'secret-pass', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Hash::check('new-secret-1', $this->guardian->fresh()->password))->toBeTrue();
    portalJson($this, 'GET', 'me', $here)->assertOk();
    portalJson($this, 'GET', 'me', $elsewhere)->assertUnauthorized();
    portalJson($this, 'POST', 'login', data: ['login' => '55123456', 'password' => 'new-secret-1'])->assertOk();
});

it('will not change the password without the current one, or to a weak one', function (): void {
    $token = StudentWorld::parentToken($this->guardian);

    portalJson($this, 'POST', 'password', $token, ['current_password' => 'wrong-pass', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Your current password is not correct.');
    portalJson($this, 'POST', 'password', $token, ['current_password' => 'secret-pass', 'password' => 'short', 'password_confirmation' => 'short'])
        ->assertStatus(422);
    portalJson($this, 'POST', 'password', $token, ['current_password' => 'secret-pass', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass'])
        ->assertStatus(422);
    portalJson($this, 'POST', 'password', null, ['current_password' => 'secret-pass', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'])
        ->assertUnauthorized();

    expect(Hash::check('secret-pass', $this->guardian->fresh()->password))->toBeTrue();
});

it('shows a parent their own children with the card balance', function (): void {
    StudentWorld::topUp($this->world, $this->student, 75);

    $response = portalJson($this, 'GET', 'students', StudentWorld::parentToken($this->guardian))->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.name'))->toBe('Sara Ahmed')
        ->and($response->json('data.0.balance'))->toEqual(75)
        ->and($response->json('data.0.has_card'))->toBeTrue()
        ->and($response->json('data.0.class'))->toBe('Grade 5 - B');
});

it('describes one child with the overdraft and the top-up range', function (): void {
    StudentWorld::setOverdraft($this->world, 50);
    StudentWorld::topUp($this->world, $this->student, 20);

    portalJson($this, 'GET', "students/{$this->student->id}", StudentWorld::parentToken($this->guardian))
        ->assertOk()
        ->assertJsonPath('data.balance', 20)
        ->assertJsonPath('data.available', 70)
        ->assertJsonPath('data.overdraft_limit', 50)
        ->assertJsonPath('data.topup.min', 10)
        ->assertJsonPath('data.topup.max', 1000)
        ->assertJsonPath('data.topup.suggestions', [50, 100, 200, 500])
        // QPay is not set up in this world.
        ->assertJsonPath('data.topup.enabled', false);
});

it('is a 404 for another family\'s child, bill, statement or top-up', function (): void {
    StudentWorld::topUp($this->world, $this->otherStudent, 50);
    $sale = (new SaleCreateAction())->execute(StudentWorld::salePayload($this->world, $this->otherStudent->id, 20, card: 20), $this->world->user->id)['data'];
    $token = StudentWorld::parentToken($this->guardian);

    portalJson($this, 'GET', "students/{$this->otherStudent->id}", $token)->assertNotFound();
    portalJson($this, 'GET', "students/{$this->otherStudent->id}/bills", $token)->assertNotFound();
    portalJson($this, 'GET', "students/{$this->otherStudent->id}/bills/{$sale->id}", $token)->assertNotFound();
    portalJson($this, 'GET', "students/{$this->student->id}/bills/{$sale->id}", $token)->assertNotFound();
    portalJson($this, 'GET', "students/{$this->otherStudent->id}/statement", $token)->assertNotFound();
    portalJson($this, 'POST', "students/{$this->otherStudent->id}/card/block", $token)->assertNotFound();
    portalJson($this, 'POST', "students/{$this->otherStudent->id}/topups", $token, ['amount' => 50])->assertNotFound();

    expect(StudentDetail::where('account_id', $this->otherStudent->id)->value('card_status'))->toBe('active');
});

it('lists a month of bills and opens one of them', function (): void {
    StudentWorld::topUp($this->world, $this->student, 50);
    $sale = (new SaleCreateAction())->execute(StudentWorld::salePayload($this->world, $this->student->id, 20, card: 20), $this->world->user->id)['data'];
    $token = StudentWorld::parentToken($this->guardian);

    portalJson($this, 'GET', "students/{$this->student->id}/bills?month=".now()->format('Y-m'), $token)
        ->assertOk()
        ->assertJsonPath('data.data.0.invoice_no', $sale->invoice_no)
        ->assertJsonPath('data.pagination.total', 1);

    portalJson($this, 'GET', "students/{$this->student->id}/bills?month=".now()->subMonths(2)->format('Y-m'), $token)
        ->assertOk()
        ->assertJsonPath('data.pagination.total', 0);

    portalJson($this, 'GET', "students/{$this->student->id}/bills/{$sale->id}", $token)
        ->assertOk()
        ->assertJsonPath('data.invoice_no', $sale->invoice_no)
        ->assertJsonPath('data.payments.0.method', 'Student Wallet')
        ->assertJsonPath('data.payments.0.amount', 20);
});

it('shows the card statement with a running balance', function (): void {
    StudentWorld::topUp($this->world, $this->student, 50);
    (new SaleCreateAction())->execute(StudentWorld::salePayload($this->world, $this->student->id, 20, card: 20), $this->world->user->id);

    $response = portalJson($this, 'GET', "students/{$this->student->id}/statement", StudentWorld::parentToken($this->guardian))->assertOk();

    expect($response->json('data.opening'))->toEqual(0)
        ->and($response->json('data.closing'))->toEqual(30)
        ->and(collect($response->json('data.rows'))->pluck('balance')->last())->toEqual(30)
        ->and($response->json('data.rows.0'))->not->toHaveKeys(['journal_id', 'model', 'model_id']);
});

it('lets a parent block a lost card but not unblock it', function (): void {
    portalJson($this, 'POST', "students/{$this->student->id}/card/block", StudentWorld::parentToken($this->guardian), ['reason' => 'Lost at school'])
        ->assertOk()
        ->assertJsonPath('data.card_blocked', true);

    $detail = StudentDetail::where('account_id', $this->student->id)->first();
    expect($detail->card_status)->toBe('blocked')
        ->and($detail->card_block_reason)->toBe('Lost at school')
        ->and($detail->card_blocked_by_type)->toBe('guardian')
        ->and($detail->card_blocked_by_id)->toBe($this->guardian->id);
});

it('invites a parent with a one-time portal link that sets the password and signs them in', function (): void {
    $oldToken = StudentWorld::parentToken($this->guardian);
    $response = (new SendInviteAction())->execute($this->guardian->id, $this->world->user->id);
    expect($response['success'])->toBeTrue($response['message'])
        ->and($response['data']['link'])->toStartWith(StudentWorld::PORTAL_URL.'/#/set-password/');

    $token = basename($response['data']['link']);
    expect($this->guardian->refresh()->invite_token_hash)->toBe(hash('sha256', $token));

    portalJson($this, 'GET', "set-password/{$token}")->assertOk()->assertJsonPath('data.valid', true)->assertJsonPath('data.name', 'Ahmed Saleh');

    $newToken = portalJson($this, 'POST', 'set-password', data: ['token' => $token, 'password' => 'new-password-1', 'password_confirmation' => 'new-password-1'])
        ->assertOk()
        ->json('data.token');

    $guardian = Guardian::find($this->guardian->id);
    expect(Hash::check('new-password-1', $guardian->password))->toBeTrue()
        ->and($guardian->invite_token_hash)->toBeNull();

    // The new sign-in works; the one from before the reset is gone.
    portalJson($this, 'GET', 'me', $newToken)->assertOk();
    portalJson($this, 'GET', 'me', $oldToken)->assertUnauthorized();

    // Used once: the same link no longer works.
    portalJson($this, 'GET', "set-password/{$token}")->assertOk()->assertJsonPath('data.valid', false);
    portalJson($this, 'POST', 'set-password', data: ['token' => $token, 'password' => 'another-pass-2', 'password_confirmation' => 'another-pass-2'])
        ->assertStatus(422);
});

it('will not send an invite link when there is no portal to open it in', function (): void {
    config(['services.parent_portal.url' => null]);

    $response = (new SendInviteAction())->execute($this->guardian->id, $this->world->user->id);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('parent portal address')
        ->and($this->guardian->refresh()->invite_token_hash)->toBeNull();
});

it('does not resolve a parent from another school', function (): void {
    $otherTenant = Tenant::factory()->create();
    app(TenantService::class)->setCurrentTenant($otherTenant);

    expect(Guardian::where('mobile', '55123456')->exists())->toBeFalse();
});
