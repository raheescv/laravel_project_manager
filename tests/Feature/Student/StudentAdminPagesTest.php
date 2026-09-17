<?php

use App\Actions\Student\GetBalanceAction;
use App\Actions\Student\ListTopupsAction;
use App\Exports\StudentExport;
use App\Imports\StudentImport;
use App\Livewire\Settings\QPayPayments;
use App\Livewire\Settings\StudentConfiguration;
use App\Livewire\Student\CardTab;
use App\Livewire\Student\Page;
use App\Livewire\Student\Purchases;
use App\Livewire\Student\Statement;
use App\Livewire\Student\Table;
use App\Livewire\Student\Topups;
use App\Livewire\Student\View;
use App\Mail\AppointmentMail;
use App\Models\Account;
use App\Models\Guardian;
use App\Models\JournalEntry;
use App\Models\StudentDetail;
use App\Support\Payment\QPaySettings;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();
    StudentWorld::enableSchool($this->world);

    foreach (config('permissions') as $group => $actions) {
        if (! str_starts_with($group, 'student')) {
            continue;
        }
        foreach ($actions as $action) {
            $this->world->user->givePermissionTo(Permission::firstOrCreate([
                'tenant_id' => $this->world->tenant->id, 'name' => "{$group}.{$action}", 'guard_name' => 'web',
            ]));
        }
    }

    $this->actingAs($this->world->user);
    $this->student = StudentWorld::enrol($this->world);
});

it('lists students with class, parent, card and balance', function (): void {
    StudentWorld::topUp($this->world, $this->student, 42.5);

    Livewire::test(Table::class)
        ->assertOk()
        ->assertSee('Sara Ahmed')
        ->assertSee('Grade 5 - B')
        ->assertSee('Ahmed Saleh')
        ->assertSee('42.50')
        ->set('search', '04a21b9c')
        ->assertSee('Sara Ahmed')
        ->set('search', 'nobody-matches')
        ->assertDontSee('Sara Ahmed');
});

it('says how many students the default active filter hides and shows them on request', function (): void {
    StudentDetail::where('account_id', $this->student->id)->update(['status' => 'inactive']);

    Livewire::test(Table::class)
        ->assertSet('status', 'active')
        ->assertDontSee('Sara Ahmed')
        ->assertSee('1 student is hidden by them')
        ->call('clearFilters')
        ->assertSet('status', '')
        ->assertSee('Sara Ahmed');
});

it('creates a student with parents from the form', function (): void {
    Livewire::test(Page::class)
        ->set('student.name', 'Yusuf Khan')
        ->set('student.admission_no', 'ADM-7788')
        ->set('student.grade', 'Grade 2')
        ->set('guardians.0.name', 'Imran Khan')
        ->set('guardians.0.mobile', '77001122')
        ->call('addGuardian')
        ->set('guardians.1.name', 'Ayesha Khan')
        ->set('guardians.1.mobile', '77003344')
        ->set('guardians.1.relation', 'mother')
        ->call('save')
        ->assertRedirect();

    $account = Account::student()->where('name', 'Yusuf Khan')->sole();
    expect($account->studentDetail->admission_no)->toBe('ADM-7788')
        ->and($account->guardians()->count())->toBe(2);
});

it('renders the student view and every tab', function (): void {
    Livewire::test(View::class, ['account_id' => $this->student->id])
        ->assertOk()
        ->assertSee('Sara Ahmed')
        ->call('selectTab', 'card')
        ->call('selectTab', 'statement')
        ->call('selectTab', 'purchases')
        ->call('selectTab', 'topups')
        ->assertOk();

    Livewire::test(CardTab::class, ['account_id' => $this->student->id])->assertOk()->assertSee('04A21B9C');
    Livewire::test(Statement::class, ['account_id' => $this->student->id])->assertOk();
    Livewire::test(Purchases::class, ['account_id' => $this->student->id])->assertOk()->assertSee('No purchases in this period');
    Livewire::test(Topups::class, ['account_id' => $this->student->id])->assertOk()->assertSee('Nothing on the card yet');

    $this->get($this->world->url("/student/view/{$this->student->id}"))->assertOk();
    $this->get($this->world->url('/student'))->assertOk();
    $this->get($this->world->url('/student/create'))->assertOk();
});

it('shows the card balance and last top-up on the student view', function (): void {
    StudentWorld::topUp($this->world, $this->student, 42.5);

    Livewire::test(View::class, ['account_id' => $this->student->id])
        ->assertSee('Last top-up')
        ->assertSee('42.50')
        ->assertSee('Grade 5 - B');
});

it('moves the statement period with the quick buttons and clears the button when dates are typed', function (): void {
    StudentWorld::topUp($this->world, $this->student, 30);

    Livewire::test(Statement::class, ['account_id' => $this->student->id])
        ->assertSet('preset', 'month')
        ->assertSet('from_date', now()->startOfMonth()->toDateString())
        ->assertSee('30.00')
        ->call('applyPreset', 'last_month')
        ->assertSet('from_date', now()->subMonthNoOverflow()->startOfMonth()->toDateString())
        ->assertSet('to_date', now()->subMonthNoOverflow()->endOfMonth()->toDateString())
        ->assertSee('No card activity in this period')
        ->set('to_date', now()->toDateString())
        ->assertSet('preset', null);
});

it('blocks, unblocks and replaces a card from the card tab', function (): void {
    Livewire::test(CardTab::class, ['account_id' => $this->student->id])
        ->set('block_reason', 'Lost')
        ->call('block')
        ->assertDispatched('success');
    expect(StudentDetail::where('account_id', $this->student->id)->value('card_status'))->toBe('blocked');

    Livewire::test(CardTab::class, ['account_id' => $this->student->id])
        ->set('card_uid', 'de:ad:be:ef')
        ->call('assign')
        ->assertDispatched('success');

    $detail = StudentDetail::where('account_id', $this->student->id)->first();
    expect($detail->card_uid)->toBe('DEADBEEF')->and($detail->card_status)->toBe('active');
});

it('invites a parent from the student view and shows the link', function (): void {
    Mail::fake();
    $guardian = $this->student->guardians->first();
    $guardian->forceFill(['email' => 'parent@example.com'])->save();

    Livewire::test(View::class, ['account_id' => $this->student->id])
        ->call('invite', $guardian->id)
        ->assertDispatched('success')
        ->assertSet('invite_guardian_id', $guardian->id)
        ->assertSee('/#/set-password/');

    expect(Guardian::find($guardian->id)->invite_token_hash)->not->toBeNull();
    Mail::assertSent(AppointmentMail::class, fn (AppointmentMail $mail) => $mail->hasTo('parent@example.com'));
});

it('warns staff and still shows the link when the parent has no email', function (): void {
    Mail::fake();
    $guardian = $this->student->guardians->first();

    Livewire::test(View::class, ['account_id' => $this->student->id])
        ->call('invite', $guardian->id)
        ->assertDispatched('warning')
        ->assertSee('/#/set-password/');

    Mail::assertNothingSent();
});

it('records an office top-up and a deduction on the card', function (): void {
    $balance = fn () => (new GetBalanceAction())->execute($this->student->id);

    Livewire::test(Topups::class, ['account_id' => $this->student->id])
        ->call('toggleForm')
        ->set('amount', '75')
        ->set('payment_account_id', (string) $this->world->cashAccountId)
        ->set('reason', 'Cash from parent at the office')
        ->call('record')
        ->assertDispatched('success')
        ->assertSet('show_form', false);

    expect($balance())->toBe(75.0)
        // The money is in the till account, and the card's statement says why.
        ->and(JournalEntry::where('account_id', $this->world->cashAccountId)->where('source', 'student_topup')->sum('debit'))->toEqual(75)
        ->and(JournalEntry::where('account_id', $this->student->id)->value('remarks'))->toContain('Cash from parent at the office');

    Livewire::test(Topups::class, ['account_id' => $this->student->id])
        ->call('toggleForm')
        ->set('direction', 'deduct')
        ->set('amount', '25')
        ->set('payment_account_id', (string) $this->world->cashAccountId)
        ->set('reason', 'Balance paid back, student left')
        ->call('record')
        ->assertDispatched('success');

    expect($balance())->toBe(50.0);

    $rows = (new ListTopupsAction())->execute($this->student->id);
    expect($rows)->toHaveCount(2)
        ->and($rows[0]['channel'])->toBe('Office')
        ->and($rows[0]['amount'])->toBe(-25.0)
        ->and($rows[0]['by'])->toBe($this->world->user->name);
});

it('will not deduct more than the card holds, or accept a blank reason', function (): void {
    Livewire::test(Topups::class, ['account_id' => $this->student->id])
        ->call('toggleForm')
        ->set('direction', 'deduct')
        ->set('amount', '10')
        ->set('payment_account_id', (string) $this->world->cashAccountId)
        ->set('reason', 'Refund')
        ->call('record')
        ->assertDispatched('error');

    Livewire::test(Topups::class, ['account_id' => $this->student->id])
        ->call('toggleForm')
        ->set('amount', '10')
        ->set('payment_account_id', (string) $this->world->cashAccountId)
        ->set('reason', '  ')
        ->call('record')
        ->assertDispatched('error');

    expect((new GetBalanceAction())->execute($this->student->id))->toBe(0.0);
});

it('saves the student card and QPay settings', function (): void {
    Livewire::test(StudentConfiguration::class)
        ->set('overdraft_limit', 25)
        ->set('topup_min', 20)
        ->set('topup_max', 500)
        ->set('portal_url', 'https://parents.school.test')
        ->call('save')
        ->assertDispatched('success');

    expect(StudentSettings::current()->overdraftLimit)->toBe(25.0)
        ->and(StudentSettings::current()->portalLink('set-password/abc'))->toBe('https://parents.school.test/#/set-password/abc');

    $bank = $this->world->addPaymentMethod('QPay Bank');
    Livewire::test(QPayPayments::class)
        ->set('enabled', true)
        ->set('bank_id', 'QIB')
        ->set('merchant_id', 'M123')
        ->set('secret_key', 'super-secret')
        ->set('payment_account_id', (string) $bank)
        ->set('user_id', (string) $this->world->user->id)
        ->call('save')
        ->assertDispatched('success')
        ->assertDontSee('super-secret');

    $settings = QPaySettings::current();
    expect($settings->isReady())->toBeTrue()
        ->and($settings->secretKey)->toBe('super-secret')
        ->and(\App\Models\Configuration::where('key', QPaySettings::SECRET_KEY)->value('value'))->not->toContain('super-secret');
});

it('imports students with both parents and updates by admission number', function (): void {
    $rows = collect([
        collect(['admission_no' => 'ADM-9001', 'name' => 'Lina Omar', 'grade' => 'Grade 3', 'section' => 'A', 'status' => 'active',
            'parent_name' => 'Omar Said', 'parent_mobile' => '33112233', 'parent_email' => 'omar@example.com', 'parent_relation' => 'father',
            'second_parent_name' => 'Huda Ali', 'second_parent_mobile' => '33445566', 'second_parent_relation' => 'mother']),
        collect(['admission_no' => $this->student->studentDetail->admission_no, 'name' => 'Sara Ahmed', 'grade' => 'Grade 6', 'status' => 'active']),
        collect(['admission_no' => '', 'name' => 'No Admission']),
    ]);

    $import = new StudentImport($this->world->user->id, 3, 'update');
    $import->collection($rows);

    $lina = Account::student()->where('name', 'Lina Omar')->sole();
    expect($import->created)->toBe(1)
        ->and($import->updated)->toBe(1)
        ->and($import->errors)->toHaveCount(1)
        ->and($lina->guardians()->count())->toBe(2)
        ->and($lina->guardians()->wherePivot('is_primary', true)->value('guardians.name'))->toBe('Omar Said')
        ->and(StudentDetail::where('account_id', $this->student->id)->value('grade'))->toBe('Grade 6')
        // An update with no parent columns leaves the parents alone.
        ->and($this->student->guardians()->count())->toBe(1);
});

it('exports students with the import template columns', function (): void {
    // The file name carries a timestamp; freeze the clock so it cannot tick between the call and the assertion.
    $this->freezeTime();
    Excel::fake();

    Livewire::test(Table::class)->call('export');

    Excel::assertDownloaded('students_'.now()->timestamp.'.xlsx', fn (StudentExport $export) => $export->filters['status'] === 'active');
});
