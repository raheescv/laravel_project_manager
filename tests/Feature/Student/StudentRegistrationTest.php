<?php

use App\Actions\Parent\ListStudentsAction;
use App\Actions\Student\Card\FindByAdmissionNoAction;
use App\Actions\V1\Parent\LoginAction;
use App\Livewire\Settings\StudentConfiguration;
use App\Livewire\Student\RegistrationTable;
use App\Livewire\Student\Table as StudentTable;
use App\Mail\AppointmentMail;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Guardian;
use App\Models\StudentDetail;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

beforeEach(function (): void {
    $this->world = PosWorld::create();
    StudentWorld::enableSchool($this->world);
    Configuration::updateOrCreate(
        ['tenant_id' => $this->world->tenant->id, 'key' => StudentSettings::KEY],
        ['value' => json_encode(['registration_schools' => ['AIA - Doha', 'AIA - Lusail']])],
    );
    $this->formUrl = $this->world->url(route('api.v1.parent.registration.form', [], false));
    $this->storeUrl = $this->world->url(route('api.v1.parent.registration.store', [], false));
});

function registrationPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'school' => 'AIA - Doha',
        'parent' => ['name' => 'Mohammed Rahman', 'qid' => '28463401234', 'phone' => '55123344', 'email' => 'mohammed@example.com'],
        'students' => [
            ['name' => 'Aisha Rahman', 'qid' => '31563401287', 'class' => '5', 'div' => 'B', 'reg' => 'REG-0187'],
            ['name' => 'Yusuf Rahman', 'qid' => '31763401555', 'class' => 'KG1', 'div' => 'A', 'reg' => 'REG-0342'],
        ],
    ], $overrides);
}

function allowRegistrations(PosWorld $world): void
{
    foreach (['student registration.view', 'student registration.approve'] as $name) {
        $world->user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
}

it('gives a guest the schools, classes and divisions from settings', function (): void {
    $this->getJson($this->formUrl)
        ->assertSuccessful()
        ->assertJsonPath('data.schools.0', ['value' => 'AIA - Doha', 'group' => 'AIA', 'place' => 'Doha', 'initial' => 'D'])
        ->assertJsonPath('data.schools.1.place', 'Lusail')
        ->assertJsonPath('data.divisions', StudentSettings::DEFAULT_REGISTRATION_DIVISIONS);
});

it('offers no schools when registration is closed', function (): void {
    Configuration::where('key', StudentSettings::KEY)->delete();

    $this->getJson($this->formUrl)
        ->assertSuccessful()
        ->assertJsonPath('data.schools', []);
});

it('does not exist for a tenant outside the School module', function (): void {
    Configuration::where('key', 'active_module')->delete();

    $this->getJson($this->formUrl)->assertNotFound();
    $this->postJson($this->storeUrl, registrationPayload())->assertNotFound();
});

it('is no longer served from the admin site', function (): void {
    $this->get($this->world->url('/student-registration'))->assertNotFound();
    $this->post($this->world->url('/student-registration'), registrationPayload())->assertNotFound();
});

it('saves each child as a pending student under a pending parent', function (): void {
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();

    $detail = StudentDetail::where('admission_no', 'REG-0342')->sole();
    $parent = Guardian::where('mobile', '55123344')->sole();
    expect(Account::student()->count())->toBe(2)
        ->and($detail->status)->toBe(StudentDetail::PENDING)
        ->and($detail->school)->toBe('AIA - Doha')
        ->and($detail->grade)->toBe('KG1')
        ->and($detail->section)->toBe('A')
        ->and($detail->account->id_no)->toBe('31763401555')
        ->and($parent->status)->toBe(Guardian::PENDING)
        ->and($parent->id_no)->toBe('28463401234')
        ->and($parent->email)->toBe('mohammed@example.com')
        ->and($parent->pendingStudents()->count())->toBe(2);
});

it('rejects a form with a bad value', function (string $errorKey, array $overrides): void {
    $this->postJson($this->storeUrl, registrationPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errorKey);

    expect(Account::student()->count())->toBe(0)
        ->and(Guardian::count())->toBe(0);
})->with([
    'unknown school' => ['school', ['school' => 'Some Other School']],
    'parent QID too short' => ['parent.qid', ['parent' => ['qid' => '123']]],
    'mobile not 8 digits' => ['parent.phone', ['parent' => ['phone' => '5512']]],
    'bad email' => ['parent.email', ['parent' => ['email' => 'nope']]],
    'student QID has letters' => ['students.0.qid', ['students' => [0 => ['qid' => '3156340128x']]]],
    'class not offered' => ['students.0.class', ['students' => [0 => ['class' => '99']]]],
    'division not offered' => ['students.0.div', ['students' => [0 => ['div' => 'Z']]]],
    'no registration number' => ['students.0.reg', ['students' => [0 => ['reg' => '']]]],
    'same QID on two children' => ['students.1.qid', ['students' => [1 => ['qid' => '31563401287']]]],
    'same number on two children' => ['students.1.reg', ['students' => [1 => ['reg' => 'reg-0187']]]],
]);

it('refuses a registration number that is already taken and saves nothing', function (): void {
    StudentWorld::enrol($this->world, ['admission_no' => 'REG-0342', 'card_uid' => null]);
    $before = Account::student()->count();

    $this->postJson($this->storeUrl, registrationPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('students.1.reg');

    expect(Account::student()->count())->toBe($before)
        ->and(Guardian::where('mobile', '55123344')->exists())->toBeFalse();
});

it('quietly drops a form whose hidden bot field is filled', function (): void {
    $this->postJson($this->storeUrl, registrationPayload() + ['contact_ref' => 'spam'])->assertSuccessful();

    expect(Account::student()->count())->toBe(0);
});

it('keeps pending students away from the students list, the till and the portal', function (): void {
    $parent = Guardian::create(['name' => 'Real Parent', 'mobile' => '55123344', 'email' => 'real@example.com', 'password' => 'secret-password', 'status' => 'active']);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();

    expect(StudentTable::filteredQuery([])->count())->toBe(0)
        ->and((new FindByAdmissionNoAction())->execute('REG-0187'))->toBeNull()
        ->and((new ListStudentsAction())->execute($parent))->toBe([]);
});

it('never lets a pending parent sign in or shadow a real parent email', function (): void {
    $this->postJson($this->storeUrl, registrationPayload(['parent' => ['email' => 'shared@example.com']]))->assertCreated();
    $real = Guardian::create(['name' => 'Real Parent', 'mobile' => '55999999', 'email' => 'shared@example.com', 'password' => 'secret-password', 'status' => 'active']);

    expect(LoginAction::findGuardian('shared@example.com')->id)->toBe($real->id)
        ->and(Guardian::where('mobile', '55123344')->sole()->isActive())->toBeFalse();
});

it('activates the family and invites the new parent on approval', function (): void {
    Mail::fake();
    allowRegistrations($this->world);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $parent = Guardian::where('mobile', '55123344')->sole();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)->call('approve', $parent->id);

    expect(StudentDetail::where('status', 'active')->count())->toBe(2)
        ->and($parent->fresh()->status)->toBe(Guardian::ACTIVE)
        ->and($parent->fresh()->students()->count())->toBe(2)
        ->and(StudentTable::filteredQuery([])->count())->toBe(2);

    Mail::assertSent(AppointmentMail::class, fn ($mail) => $mail->hasTo('mohammed@example.com'));
});

it('never lets the public form change an existing parent or redirect their login email', function (): void {
    Mail::fake();
    allowRegistrations($this->world);
    $existing = Guardian::create([
        'name' => 'Real Parent', 'mobile' => '55123344', 'email' => 'real@example.com', 'id_no' => '11111111111',
        'password' => 'secret-password', 'status' => 'active',
    ]);

    $this->postJson($this->storeUrl, registrationPayload(['parent' => ['email' => 'attacker@example.com']]))->assertCreated();

    $existing->refresh();
    expect($existing->email)->toBe('real@example.com')
        ->and($existing->name)->toBe('Real Parent')
        ->and($existing->id_no)->toBe('11111111111')
        ->and($existing->status)->toBe(Guardian::ACTIVE)
        ->and(Account::student()->first()->description)->toContain('attacker@example.com');

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)->call('approve', $existing->id);

    expect($existing->fresh()->students()->count())->toBe(2);
    Mail::assertNothingSent();
});

it('activates a pending parent when staff enrol them directly', function (): void {
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();

    StudentWorld::enrol($this->world, ['card_uid' => null, 'guardians' => [['name' => 'Mohammed Rahman', 'mobile' => '55123344', 'relation' => 'father']]]);

    expect(Guardian::where('mobile', '55123344')->sole()->status)->toBe(Guardian::ACTIVE);
});

it('removes what the form created on rejection and frees the numbers', function (): void {
    allowRegistrations($this->world);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->call('reject', Guardian::where('mobile', '55123344')->sole()->id)
        ->assertDispatched('success');

    expect(Account::student()->count())->toBe(0)
        ->and(StudentDetail::count())->toBe(0)
        ->and(Guardian::where('mobile', '55123344')->exists())->toBeFalse();

    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
});

it('keeps an existing parent and their enrolled children when a registration is rejected', function (): void {
    allowRegistrations($this->world);
    $enrolled = StudentWorld::enrol($this->world, ['card_uid' => null, 'guardians' => [['name' => 'Real Parent', 'mobile' => '55123344', 'relation' => 'father']]]);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $parent = Guardian::where('mobile', '55123344')->sole();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)->call('reject', $parent->id);

    expect($parent->fresh())->not->toBeNull()
        ->and($parent->fresh()->students()->pluck('accounts.id')->all())->toBe([$enrolled->id])
        ->and(Account::student()->count())->toBe(1);
});

it('will not act on a family with nothing waiting', function (): void {
    allowRegistrations($this->world);
    $parent = Guardian::create(['name' => 'Real Parent', 'mobile' => '55123344', 'status' => 'active']);

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->call('approve', $parent->id)
        ->assertDispatched('error');
});

it('needs the approve permission', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'student registration.view', 'guard_name' => 'web']));
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $parent = Guardian::where('mobile', '55123344')->sole();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->call('approve', $parent->id)
        ->assertForbidden();

    expect($parent->fresh()->isPendingApproval())->toBeTrue();
});

function secondFamilyPayload(array $overrides = []): array
{
    return registrationPayload(array_replace_recursive([
        'school' => 'AIA - Lusail',
        'parent' => ['name' => 'Fatima Noor', 'phone' => '66778899', 'email' => 'fatima@example.com'],
        'students' => [
            ['name' => 'Omar Noor', 'qid' => '31563409999', 'reg' => 'REG-0900'],
            ['name' => 'Layla Noor', 'qid' => '31763408888', 'reg' => 'REG-0901'],
        ],
    ], $overrides));
}

it('approves every ticked family at once and invites the new parents', function (): void {
    Mail::fake();
    allowRegistrations($this->world);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $this->postJson($this->storeUrl, secondFamilyPayload())->assertCreated();
    $ids = Guardian::pluck('id')->map(fn ($id) => (string) $id)->all();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->set('selected', $ids)
        ->assertSee('2 families')
        ->call('approveSelected')
        ->assertDispatched('success')
        ->assertSet('selected', []);

    expect(StudentDetail::where('status', 'active')->count())->toBe(4)
        ->and(Guardian::where('status', Guardian::PENDING)->count())->toBe(0);

    Mail::assertSent(AppointmentMail::class, fn ($mail) => $mail->hasTo('mohammed@example.com'));
    Mail::assertSent(AppointmentMail::class, fn ($mail) => $mail->hasTo('fatima@example.com'));
});

it('leaves unticked families waiting', function (): void {
    Mail::fake();
    allowRegistrations($this->world);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $this->postJson($this->storeUrl, secondFamilyPayload())->assertCreated();
    $ticked = Guardian::where('mobile', '55123344')->sole();
    $unticked = Guardian::where('mobile', '66778899')->sole();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->set('selected', [(string) $ticked->id])
        ->call('approveSelected');

    expect($ticked->fresh()->pendingStudents()->count())->toBe(0)
        ->and($unticked->fresh()->pendingStudents()->count())->toBe(2)
        ->and($unticked->fresh()->isPendingApproval())->toBeTrue();
});

it('still approves the rest when one ticked family cannot be approved', function (): void {
    Mail::fake();
    allowRegistrations($this->world);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $waiting = Guardian::where('mobile', '55123344')->sole();
    $handled = Guardian::create(['name' => 'Done Already', 'mobile' => '70000000', 'status' => 'active']);

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->set('selected', [(string) $handled->id, (string) $waiting->id])
        ->call('approveSelected')
        ->assertDispatched('warning');

    expect($waiting->fresh()->pendingStudents()->count())->toBe(0);
});

it('does not send a portal link to an existing parent in a bulk approval', function (): void {
    Mail::fake();
    allowRegistrations($this->world);
    $existing = Guardian::create(['name' => 'Real Parent', 'mobile' => '55123344', 'email' => 'real@example.com', 'password' => 'secret-password', 'status' => 'active']);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->set('selected', [(string) $existing->id])
        ->call('approveSelected');

    expect($existing->fresh()->students()->count())->toBe(2);
    Mail::assertNothingSent();
});

it('needs the approve permission to approve in bulk', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'student registration.view', 'guard_name' => 'web']));
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $parent = Guardian::where('mobile', '55123344')->sole();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->set('selected', [(string) $parent->id])
        ->call('approveSelected')
        ->assertForbidden();

    expect($parent->fresh()->pendingStudents()->count())->toBe(2);
});

it('selects only the families the filters show and forgets the ticks when a filter changes', function (): void {
    allowRegistrations($this->world);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $this->postJson($this->storeUrl, secondFamilyPayload())->assertCreated();
    $lusail = Guardian::where('mobile', '66778899')->sole();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->set('school', 'AIA - Lusail')
        ->set('selectAll', true)
        ->assertSet('selected', [(string) $lusail->id])
        ->set('school', '')
        ->assertSet('selected', [])
        ->assertSet('selectAll', false);
});

it('filters by new or existing parents and counts what is waiting', function (): void {
    allowRegistrations($this->world);
    Guardian::create(['name' => 'Real Parent', 'mobile' => '66778899', 'password' => 'secret-password', 'status' => 'active']);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $this->postJson($this->storeUrl, secondFamilyPayload())->assertCreated();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->assertViewHas('stats', ['families' => 2, 'students' => 4, 'existing' => 1])
        ->assertViewHas('schools', fn ($schools) => $schools->all() === ['AIA - Doha' => 1, 'AIA - Lusail' => 1])
        ->set('parentType', 'existing')
        ->assertSee('Real Parent')
        ->assertDontSee('Mohammed Rahman')
        ->set('parentType', 'new')
        ->assertSee('Mohammed Rahman')
        ->assertDontSee('Real Parent');
});

it('closes the view modal once a family has been approved or rejected', function (): void {
    Mail::fake();
    allowRegistrations($this->world);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $this->postJson($this->storeUrl, secondFamilyPayload())->assertCreated();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->call('approve', Guardian::where('mobile', '55123344')->sole()->id)
        ->assertDispatched('hide-registration-modal');

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->call('reject', Guardian::where('mobile', '66778899')->sole()->id)
        ->assertDispatched('hide-registration-modal');
});

it('shows everything the parent submitted in the view modal', function (): void {
    allowRegistrations($this->world);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $parent = Guardian::where('mobile', '55123344')->sole();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->call('viewFamily', $parent->id)
        ->assertDispatched('show-registration-modal')
        ->assertSeeInOrder(['Registration details', 'Mohammed Rahman', '55123344', '28463401234', 'mohammed@example.com'])
        ->assertSeeInOrder(['Aisha Rahman', 'AIA - Doha', '5', 'B', 'REG-0187', '31563401287'])
        ->assertSeeInOrder(['Yusuf Rahman', 'AIA - Doha', 'KG1', 'A', 'REG-0342', '31763401555']);
});

it('says so when the family in the view modal has already been handled', function (): void {
    Mail::fake();
    allowRegistrations($this->world);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $parent = Guardian::where('mobile', '55123344')->sole();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->call('viewFamily', $parent->id)
        ->call('approve', $parent->id)
        ->assertSee('This registration has already been handled.');
});

it('needs the view permission to open the view modal', function (): void {
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();
    $parent = Guardian::where('mobile', '55123344')->sole();

    Livewire::actingAs($this->world->user)->test(RegistrationTable::class)
        ->call('viewFamily', $parent->id)
        ->assertForbidden();
});

it('lists waiting families for staff', function (): void {
    allowRegistrations($this->world);
    $this->postJson($this->storeUrl, registrationPayload())->assertCreated();

    $this->actingAs($this->world->user)->get($this->world->url(route('student::registrations', [], false)))
        ->assertSuccessful()
        ->assertSee('Mohammed Rahman')
        ->assertSee('Aisha Rahman')
        ->assertSee('REG-0187');
});

it('keeps the public page address off the staff screens', function (): void {
    foreach (['student settings.edit', 'student registration.view'] as $permission) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }

    $this->actingAs($this->world->user)->get($this->world->url(route('student::registrations', [], false)))
        ->assertOk()
        ->assertDontSee('student-registration', false);

    Livewire::actingAs($this->world->user)->test(StudentConfiguration::class)
        ->assertDontSee('student-registration', false);
});

it('saves the schools, classes and divisions from settings without touching other settings', function (): void {
    $this->world->user->givePermissionTo(Permission::firstOrCreate(['name' => 'student settings.edit', 'guard_name' => 'web']));

    Livewire::actingAs($this->world->user)->test(StudentConfiguration::class)
        ->set('registration_schools', "AIA - Doha\n\n AIA - Lusail \n")
        ->set('registration_classes', "1\n2")
        ->set('registration_divisions', "A\nB")
        ->set('topup_min', 25)
        ->call('save')
        ->assertHasNoErrors();

    $settings = StudentSettings::current();
    expect($settings->registrationSchools)->toBe(['AIA - Doha', 'AIA - Lusail'])
        ->and($settings->registrationClasses)->toBe(['1', '2'])
        ->and($settings->registrationDivisions)->toBe(['A', 'B'])
        ->and($settings->topupMin)->toBe(25.0);
});
