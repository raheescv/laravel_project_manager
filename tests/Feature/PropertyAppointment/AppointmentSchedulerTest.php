<?php

use App\Actions\PropertyAppointment\Availability\CreateDefaultsAction as AvailabilityDefaultsAction;
use App\Actions\PropertyAppointment\BookAction;
use App\Actions\PropertyAppointment\CancelAction;
use App\Actions\PropertyAppointment\CreateAction;
use App\Actions\PropertyAppointment\RevokeLinkAction;
use App\Actions\PropertyAppointment\SendLinkAction;
use App\Actions\PropertyAppointment\StatusAction;
use App\Actions\Settings\EmailTemplate\CreateAction as TemplateCreateAction;
use App\Actions\Settings\EmailTemplate\CreateDefaultsAction;
use App\Actions\Settings\EmailTemplate\UpdateAction as TemplateUpdateAction;
use App\Livewire\RentOut\Tabs\AppointmentTab;
use App\Models\Account;
use App\Models\Branch;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Property;
use App\Models\PropertyAppointment;
use App\Models\PropertyAppointmentAvailability;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Models\RentOut;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkingDay;
use App\Services\EmailTemplateRenderer;
use App\Services\PropertyAppointment\AppointmentMailData;
use App\Services\PropertyAppointment\SlotService;
use App\Services\TenantService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/**
 * The appointment scheduler's one non-negotiable invariant is that an employee can
 * never be double-booked for the same moment. It is enforced by a unique index
 * over a generated column, so these tests drive the real database rather than
 * mocking the guard they are supposed to be proving.
 */

/** Minimal tenant + agreement the scheduler can hang off. */
function vsSeed(): array
{
    $tenant = Tenant::create(['name' => 'VS Tenant', 'subdomain' => 'vs'.uniqid(), 'is_active' => 1]);
    app(TenantService::class)->setCurrentTenant($tenant);
    session(['tenant_id' => $tenant->id, 'branch_code' => 'M']);

    $employee = User::create([
        'tenant_id' => $tenant->id, 'name' => 'VS Employee', 'type' => 'employee',
        'email' => 'vs'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);

    $customer = Account::create([
        'tenant_id' => $tenant->id, 'account_type' => 'asset', 'name' => 'VS Customer',
        'email' => 'customer'.uniqid().'@example.test', 'mobile' => '30000000',
    ]);

    // rent_outs requires the whole property chain — branch, group, building,
    // type and the property itself are all non-null foreign keys.
    $branch = Branch::create(['tenant_id' => $tenant->id, 'name' => 'VS Branch', 'code' => 'VS']);
    session(['branch_id' => $branch->id]);

    $group = PropertyGroup::create(['tenant_id' => $tenant->id, 'name' => 'VS Group']);
    $building = PropertyBuilding::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id,
        'property_group_id' => $group->id, 'name' => 'VS Building',
    ]);
    $type = PropertyType::create(['tenant_id' => $tenant->id, 'name' => 'VS Type']);
    $property = Property::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id,
        'property_group_id' => $group->id, 'property_building_id' => $building->id,
        'property_type_id' => $type->id, 'number' => 'VS-101',
    ]);

    $rentOut = RentOut::create([
        'tenant_id' => $tenant->id, 'branch_id' => $branch->id, 'account_id' => $customer->id,
        'salesman_id' => $employee->id, 'property_id' => $property->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id,
        'property_group_id' => $group->id, 'agreement_type' => 'lease',
        'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
        'created_by' => $employee->id,
    ]);

    foreach (range(0, 6) as $dayOfWeek) {
        PropertyAppointmentAvailability::create([
            'tenant_id' => $tenant->id, 'user_id' => $employee->id, 'day_of_week' => $dayOfWeek,
            'start_time' => '09:00', 'end_time' => '13:00',
            'is_active' => true,
        ]);
    }

    return compact('tenant', 'employee', 'customer', 'rentOut', 'branch', 'property');
}

/** Grant one ability to a user, creating the permission row if needed. */
function vsGrant(App\Models\User $user, string $ability): void
{
    $user->givePermissionTo(
        Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => $ability, 'guard_name' => 'web', 'tenant_id' => $user->tenant_id]
        )
    );
}

function vsFirstSlot(int $employeeId): string
{
    $slots = app(SlotService::class)->availableSlots($employeeId);
    $day = array_key_first($slots);

    return $slots[$day][0]['value'];
}

it('generates slots only inside the employee\'s availability', function () {
    $seed = vsSeed();

    $slots = app(SlotService::class)->availableSlots($seed['employee']->id);

    expect($slots)->not->toBeEmpty();

    foreach ($slots as $day => $daySlots) {
        foreach ($daySlots as $slot) {
            $hour = (int) Carbon\Carbon::parse($slot['value'])->format('G');
            expect($hour)->toBeGreaterThanOrEqual(9)->toBeLessThan(13);
        }
    }
});

it('labels slots on a 12-hour clock while keeping the value machine-readable', function () {
    $seed = vsSeed();

    $slots = app(SlotService::class)->availableSlots($seed['employee']->id);
    $first = collect($slots)->flatten(1)->first();

    // The value is what BookAction and the unique index rely on, so it must
    // stay canonical no matter how the label is displayed.
    expect($first['value'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/')
        ->and($first['label'])->toMatch('/^\d{2}:\d{2} (AM|PM)$/');
});

it('never offers a slot that is already booked', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $slot = vsFirstSlot($seed['employee']->id);

    (new BookAction())->execute($appointment->id, $slot);

    $after = app(SlotService::class)->availableSlots($seed['employee']->id);
    $stillOffered = collect($after)->flatten(1)->contains('value', $slot);

    expect($stillOffered)->toBeFalse();
});

it('refuses to double-book an employee for the same moment', function () {
    $seed = vsSeed();
    $first = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $second = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $slot = vsFirstSlot($seed['employee']->id);

    expect((new BookAction())->execute($first->id, $slot)['success'])->toBeTrue();

    $response = (new BookAction())->execute($second->id, $slot);

    expect($response['success'])->toBeFalse()
        ->and(PropertyAppointment::where('status', 'scheduled')->where('scheduled_at', $slot)->count())->toBe(1);
});

it('releases the slot again when a appointment is cancelled', function () {
    $seed = vsSeed();
    $first = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $second = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $slot = vsFirstSlot($seed['employee']->id);

    (new BookAction())->execute($first->id, $slot);
    (new CancelAction())->execute($first->id, $seed['employee']->id, 'testing');

    expect((new BookAction())->execute($second->id, $slot)['success'])->toBeTrue();
});

it('will not open a appointment with nobody to carry it out', function () {
    $seed = vsSeed();

    $response = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], 1);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('select the employee');
});

it('opens a appointment on an employee the agreement knows nothing about', function () {
    $seed = vsSeed();

    // Deliberately not the agreement's salesman: the two are unrelated now.
    $other = User::create([
        'tenant_id' => $seed['tenant']->id, 'name' => 'VS Other', 'type' => 'employee',
        'email' => 'vso'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);

    $seed['rentOut']->update(['salesman_id' => null]);

    $response = (new CreateAction())->execute([
        'rent_out_id' => $seed['rentOut']->id,
        'employee_id' => $other->id,
    ], 1);

    expect($response['success'])->toBeTrue()
        ->and($response['data']->employee_id)->toBe($other->id);
});

it('refuses to send when no template is active for the type', function () {
    vsSeed();

    expect(fn () => app(EmailTemplateRenderer::class)->activeTemplate(AppointmentMailData::MODULE, 'appointment_invite'))
        ->toThrow(Exception::class);
});

it('rejects a template that uses an unknown merge variable', function () {
    vsSeed();

    $response = (new TemplateCreateAction())->execute([
        'module' => AppointmentMailData::MODULE, 'type' => 'appointment_invite', 'name' => 'Typo', 'subject' => 'Hi {{ custmer_name }}',
        'body' => '<p>Body</p>', 'language' => 'en',
    ]);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('custmer_name');
});

it('keeps exactly one active template per type', function () {
    vsSeed();

    $a = (new TemplateCreateAction())->execute([
        'module' => AppointmentMailData::MODULE, 'type' => 'appointment_invite', 'name' => 'A', 'subject' => 'A',
        'body' => '<p>A</p>', 'language' => 'en', 'is_active' => true,
    ])['data'];

    $b = (new TemplateCreateAction())->execute([
        'module' => AppointmentMailData::MODULE, 'type' => 'appointment_invite', 'name' => 'B', 'subject' => 'B',
        'body' => '<p>B</p>', 'language' => 'en',
    ])['data'];

    (new TemplateUpdateAction())->execute(['is_active' => true], $b->id);

    expect(EmailTemplate::find($a->id)->is_active)->toBeFalsy()
        ->and(EmailTemplate::find($b->id)->is_active)->toBeTruthy();
});

it('does not wipe the body when only toggling a template active', function () {
    vsSeed();

    $template = (new TemplateCreateAction())->execute([
        'module' => AppointmentMailData::MODULE, 'type' => 'appointment_reminder', 'name' => 'Reminder', 'subject' => 'Soon',
        'body' => '<p>Original wording</p>', 'language' => 'en',
    ])['data'];

    (new TemplateUpdateAction())->execute(['is_active' => true], $template->id);

    expect(EmailTemplate::find($template->id)->body)->toContain('Original wording');
});

it('renders the appointments tab on a lease agreement', function () {
    $seed = vsSeed();
    $this->actingAs($seed['employee']);

    Livewire::test(AppointmentTab::class, ['rentOutId' => $seed['rentOut']->id])
        ->set('employee_id', $seed['employee']->id)
        ->assertOk()
        ->assertSee('No appointment link sent yet');
});

it('asks for an employee before it offers anything else', function () {
    $seed = vsSeed();
    $this->actingAs($seed['employee']);

    Livewire::test(AppointmentTab::class, ['rentOutId' => $seed['rentOut']->id])
        ->assertOk()
        ->assertSee('No employee chosen yet')
        ->assertDontSee('No appointment link sent yet');
});

it('offers slots from the chosen employee, not the agreement salesman', function () {
    $seed = vsSeed();
    $this->actingAs($seed['employee']);

    $other = User::create([
        'tenant_id' => $seed['tenant']->id, 'name' => 'VS Other', 'type' => 'employee',
        'email' => 'vso'.uniqid().'@example.test', 'password' => bcrypt('secret'),
    ]);

    // Afternoons only, against the seeded employee's 09:00-13:00. Both are
    // bookable, so an empty grid would prove nothing — the hours the grid is
    // cut from are what has to follow the choice.
    foreach (range(0, 6) as $dayOfWeek) {
        PropertyAppointmentAvailability::create([
            'tenant_id' => $seed['tenant']->id, 'user_id' => $other->id, 'day_of_week' => $dayOfWeek,
            'start_time' => '15:00', 'end_time' => '17:00', 'is_active' => true,
        ]);
    }

    $hourOf = fn ($slots) => collect($slots)->flatten(1)->pluck('value')->map(fn ($v) => (int) substr($v, 11, 2));

    $component = Livewire::test(AppointmentTab::class, ['rentOutId' => $seed['rentOut']->id])
        ->set('employee_id', $seed['employee']->id);

    $mine = $hourOf($component->instance()->slots);

    expect($mine)->not->toBeEmpty()
        ->and($mine->max())->toBeLessThan(13);

    $component->set('employee_id', $other->id);

    $theirs = $hourOf($component->instance()->slots);

    expect($theirs)->not->toBeEmpty()
        ->and($theirs->min())->toBeGreaterThanOrEqual(15);
});

it('serves the public appointment page for a valid token', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    // Assert against the id the Vue entry actually mounts to, read from the
    // source — hardcoding it here let a rename break the page while this test
    // stayed green, because both sides were "renamed" to different words.
    preg_match(
        "/getElementById\('([^']+)'\)/",
        file_get_contents(base_path('resources/js/property-appointment.js')),
        $m
    );
    $mountId = $m[1] ?? null;

    expect($mountId)->not->toBeNull('could not read the mount id from the Vue entry');

    $this->get(route('property_appointment::public', $appointment->token))
        ->assertOk()
        ->assertSee('id="'.$mountId.'"', false);
});

it('404s an unknown appointment token', function () {
    vsSeed();

    $this->get(route('property_appointment::public', 'not-a-real-token'))->assertNotFound();
    $this->getJson(route('property_appointment::public.data', 'not-a-real-token'))->assertNotFound();
});

it('returns every slot in one payload so selection needs no round-trip', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    $response = $this->getJson(route('property_appointment::public.data', $appointment->token))->assertOk();

    $payload = $response->json();

    expect($payload['status'])->toBe('awaiting')
        ->and($payload['customer_name'])->toBe('VS Customer')
        ->and($payload['employee_name'])->toBe('VS Employee')
        ->and(count($payload['days']))->toBeGreaterThan(1)
        ->and($payload['days'][0]['slots'][0])->toHaveKeys(['value', 'label']);
});

it('books through the public endpoint and records who booked it', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $slot = vsFirstSlot($seed['employee']->id);

    $this->postJson(route('property_appointment::public.book', $appointment->token), ['slot' => $slot])
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'scheduled']);

    $appointment->refresh();

    expect($appointment->status)->toBe('scheduled')
        ->and($appointment->booked_by)->toBe('customer')
        ->and($appointment->scheduled_at->format('Y-m-d H:i:s'))->toBe($slot);
});

it('rejects a taken slot and hands back fresh times to recover with', function () {
    $seed = vsSeed();
    $first = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $second = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $slot = vsFirstSlot($seed['employee']->id);

    (new BookAction())->execute($first->id, $slot);

    $response = $this->postJson(route('property_appointment::public.book', $second->token), ['slot' => $slot])
        ->assertStatus(409)
        ->assertJson(['success' => false, 'slot_taken' => true]);

    // The losing request must be able to re-render without a second fetch,
    // and must not still be offered the slot it just lost.
    $offered = collect($response->json('days'))->flatMap(fn ($day) => $day['slots'])->pluck('value');

    expect($offered)->not->toContain($slot)
        ->and($offered->count())->toBeGreaterThan(0)
        ->and($second->refresh()->status)->toBe('awaiting');
});

it('validates the slot the public endpoint is given', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    $this->postJson(route('property_appointment::public.book', $appointment->token), [])
        ->assertStatus(422);
});

it('scopes the one-active rule to a module event, not the whole table', function () {
    vsSeed();

    $invite = (new TemplateCreateAction())->execute([
        'module' => AppointmentMailData::MODULE, 'type' => 'appointment_invite', 'name' => 'Invite',
        'subject' => 'Invite', 'body' => '<p>Invite</p>', 'language' => 'en', 'is_active' => true,
    ])['data'];

    $reminder = (new TemplateCreateAction())->execute([
        'module' => AppointmentMailData::MODULE, 'type' => 'appointment_reminder', 'name' => 'Remind',
        'subject' => 'Remind', 'body' => '<p>Remind</p>', 'language' => 'en', 'is_active' => true,
    ])['data'];

    // Different events, so activating one must not stand the other down.
    expect(EmailTemplate::find($invite->id)->is_active)->toBeTruthy()
        ->and(EmailTemplate::find($reminder->id)->is_active)->toBeTruthy();
});

it('creates a usable starter template for every appointment event', function () {
    vsSeed();

    $response = (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);

    expect($response['success'])->toBeTrue();

    $types = array_keys(EmailTemplate::typesFor(AppointmentMailData::MODULE));

    foreach ($types as $type) {
        $template = EmailTemplate::activeType(AppointmentMailData::MODULE, $type)->first();

        expect($template)->not->toBeNull("no active template for {$type}")
            ->and($template->subject)->not->toBeEmpty()
            ->and($template->body)->not->toBeEmpty();
    }
});

it('ships starter wording that only uses variables the event can resolve', function () {
    vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);

    $renderer = app(EmailTemplateRenderer::class);

    foreach (EmailTemplate::all() as $template) {
        $unknown = $renderer->unknownVariables(
            $template->subject.' '.$template->body,
            $template->module,
            $template->type
        );

        expect($unknown)->toBeEmpty(
            "{$template->type} starter references unknown variable(s): ".implode(', ', $unknown)
        );
    }
});

it('renders a starter template with no placeholder left behind', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);

    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['employee']->id));

    $rendered = app(EmailTemplateRenderer::class)->render(
        AppointmentMailData::MODULE,
        'appointment_confirmed',
        app(AppointmentMailData::class)->forAppointment($appointment->refresh())
    );

    expect($rendered['subject'])->not->toContain('{{')
        ->and($rendered['body'])->not->toContain('{{')
        ->and($rendered['body'])->toContain('VS Customer');
});

it('never overwrites wording the tenant has already edited', function () {
    vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);

    $template = EmailTemplate::activeType(AppointmentMailData::MODULE, 'appointment_invite')->first();
    $template->update(['subject' => 'Our own wording', 'body' => '<p>Our own body</p>']);

    // Running it again must be a no-op for events that already have a template.
    $second = (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);

    expect($second['success'])->toBeFalse()
        ->and($template->refresh()->subject)->toBe('Our own wording')
        ->and($template->body)->toContain('Our own body');
});

it('fills the whole default week for an employee in one press', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();

    $response = (new AvailabilityDefaultsAction())->execute($seed['employee']->id, $seed['employee']->id);

    expect($response['success'])->toBeTrue()
        ->and(PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->count())
        ->toBe(count(config('property_appointment.default_availability.days')));
});

it('uses the tenant working days rather than the config fallback when they exist', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();

    // Only Monday and Wednesday are worked here.
    foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $order => $name) {
        WorkingDay::create([
            'tenant_id' => $seed['tenant']->id,
            'day_name' => $name,
            'is_working' => in_array($name, ['Monday', 'Wednesday'], true),
            'order_no' => $order,
        ]);
    }

    (new AvailabilityDefaultsAction())->execute($seed['employee']->id, $seed['employee']->id);

    $days = PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)
        ->pluck('day_of_week')->map(fn ($d) => (int) $d)->sort()->values()->all();

    expect($days)->toBe([1, 3]);
});

it('does not duplicate or overwrite hours when pressed twice', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();

    (new AvailabilityDefaultsAction())->execute($seed['employee']->id, $seed['employee']->id);

    $first = PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->first();
    $first->update(['start_time' => '11:00', 'end_time' => '15:00']);
    $countAfterFirst = PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->count();

    $second = (new AvailabilityDefaultsAction())->execute($seed['employee']->id, $seed['employee']->id);

    expect($second['success'])->toBeFalse()
        ->and(PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->count())->toBe($countAfterFirst)
        ->and(substr((string) $first->refresh()->start_time, 0, 5))->toBe('11:00');
});

it('produces bookable slots straight after the default week is applied', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();

    (new AvailabilityDefaultsAction())->execute($seed['employee']->id, $seed['employee']->id);

    $slots = app(SlotService::class)->availableSlots($seed['employee']->id);

    expect($slots)->not->toBeEmpty();
});

it('logs a sent email with the body the recipient actually received', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    $response = (new SendLinkAction())->execute($appointment->id, 'appointment_invite', $seed['employee']->id);

    expect($response['success'])->toBeTrue();

    $log = EmailLog::latest('id')->first();

    expect($log->module)->toBe(AppointmentMailData::MODULE)
        ->and($log->type)->toBe('appointment_invite')
        ->and($log->to_email)->toBe($seed['customer']->email)
        ->and($log->body)->not->toBeEmpty()
        ->and($log->related_id)->toBe($appointment->id)
        ->and($appointment->emailLogs()->count())->toBe(1);
});

it('keeps the sent body even after the template is reworded', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    (new SendLinkAction())->execute($appointment->id, 'appointment_invite', $seed['employee']->id);
    $log = EmailLog::latest('id')->first();
    $sentBody = $log->body;

    EmailTemplate::activeType(AppointmentMailData::MODULE, 'appointment_invite')
        ->first()
        ->update(['body' => '<p>Totally different wording</p>']);

    // The log is a record of what was sent, not a live view of the template.
    expect($log->refresh()->body)->toBe($sentBody)
        ->and($log->body)->not->toContain('Totally different wording');
});

it('logs email sent by code that knows nothing about the log', function () {
    vsSeed();
    config(['mail.default' => 'array']);

    $before = EmailLog::count();

    Mail::raw('Body from somewhere else entirely', function ($message) {
        $message->to('unrelated@example.test')->subject('Unrelated email');
    });

    expect(EmailLog::count())->toBe($before + 1);

    $log = EmailLog::latest('id')->first();

    expect($log->module)->toBe('general')
        ->and($log->status)->toBe('sent')
        ->and($log->to_email)->toBe('unrelated@example.test')
        ->and($log->subject)->toBe('Unrelated email');
});

it('gates the email log behind its own permission', function () {
    $seed = vsSeed();

    $this->actingAs($seed['employee'])
        ->get(route('log::emails'))
        ->assertForbidden();
});

it('injects the styled call-to-action instead of printing its markup', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    $rendered = app(EmailTemplateRenderer::class)->render(
        AppointmentMailData::MODULE,
        'appointment_invite',
        app(AppointmentMailData::class)->forAppointment($appointment)
    );

    // The button is HTML we generate, so it must survive un-escaped — and the
    // sanitiser must not strip it, which it would if it ran after substitution.
    expect($rendered['body'])->toContain('Choose your appointment time')
        ->and($rendered['body'])->not->toContain('&lt;table')
        ->and($rendered['body'])->toContain($appointment->token);
});

it('leaves no unresolved variable in a rendered email', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['employee']->id));

    foreach (array_keys(EmailTemplate::typesFor(AppointmentMailData::MODULE)) as $type) {
        $rendered = app(EmailTemplateRenderer::class)->render(
            AppointmentMailData::MODULE,
            $type,
            app(AppointmentMailData::class)->forAppointment($appointment->refresh())
        );

        expect($rendered['subject'])->not->toContain('{{', "{$type} subject has an unresolved variable")
            ->and($rendered['body'])->not->toContain('{{', "{$type} body has an unresolved variable");
    }
});

it('applies editorial typography without touching styles a tenant set', function () {
    $styled = App\Support\EmailStyler::editorial(
        '<p>Plain</p><p style="color:red">Mine</p>',
        '#0E8A4F'
    );

    expect($styled)->toContain('Georgia')
        ->and($styled)->toContain('style="color:red"');
});

it('escapes customer data but never the generated button', function () {
    $seed = vsSeed();
    $seed['customer']->update(['name' => 'A & B <script>']);
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    $rendered = app(EmailTemplateRenderer::class)->render(
        AppointmentMailData::MODULE,
        'appointment_invite',
        app(AppointmentMailData::class)->forAppointment($appointment)
    );

    expect($rendered['body'])->toContain('&amp;')
        ->and($rendered['body'])->not->toContain('<script>');
});

it('names the building and project alongside the unit', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    $variables = app(AppointmentMailData::class)->forAppointment($appointment);

    expect($variables['unit_number'])->toBe('VS-101')
        ->and($variables['building_name'])->toBe('VS Building')
        ->and($variables['project_name'])->toBe('VS Group');
});

it('renders the unit inside its building in the invitation', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    $rendered = app(EmailTemplateRenderer::class)->render(
        AppointmentMailData::MODULE,
        'appointment_invite',
        app(AppointmentMailData::class)->forAppointment($appointment)
    );

    expect($rendered['subject'])->toContain('VS-101')
        ->and($rendered['subject'])->toContain('VS Building')
        ->and($rendered['body'])->toContain('VS Building');
});

it('never signs off blank when company profile is empty', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    // Nothing is configured in Company Profile for this tenant.
    expect(app(AppointmentMailData::class)->forAppointment($appointment)['company_name'])->not->toBeEmpty();
});

it('reflects a status change without needing a page refresh', function () {
    $seed = vsSeed();
    vsGrant($seed['employee'], 'property appointment.edit');
    $this->actingAs($seed['employee']);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['employee']->id));

    $component = Livewire::test(AppointmentTab::class, ['rentOutId' => $seed['rentOut']->id])
        ->assertSee('Confirmed');

    $component->call('markStatus', 'completed');

    // Livewire memoises getXProperty() for the whole request, so without
    // clearing it the panel would redraw the old status until a reload.
    expect(PropertyAppointment::find($appointment->id)->status)->toBe('completed');

    $component->assertSee('Completed');
});

it('lets a no-show customer book again from the same link', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['employee']->id));
    (new StatusAction())->execute($appointment->id, 'no_show', $seed['employee']->id);

    $payload = $this->getJson(route('property_appointment::public.data', $appointment->token))->assertOk()->json();

    expect($payload['status'])->toBe('no_show')
        ->and($payload['usable'])->toBeTrue()
        ->and($payload['days'])->not->toBeEmpty();

    $this->postJson(route('property_appointment::public.book', $appointment->token), ['slot' => vsFirstSlot($seed['employee']->id)])
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'scheduled']);
});

it('lets a cancelled customer book again from the same link', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['employee']->id));
    (new CancelAction())->execute($appointment->id, $seed['employee']->id, 'testing');

    $this->postJson(route('property_appointment::public.book', $appointment->token), ['slot' => vsFirstSlot($seed['employee']->id)])
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'scheduled']);
});

it('closes the link once the visit is completed', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['employee']->id));
    (new StatusAction())->execute($appointment->id, 'completed', $seed['employee']->id);

    $payload = $this->getJson(route('property_appointment::public.data', $appointment->token))->assertOk()->json();

    expect($payload['usable'])->toBeFalse()
        ->and($payload['days'])->toBeEmpty();
});

it('still lets staff revoke a link deliberately', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    (new RevokeLinkAction())->execute($appointment->id, $seed['employee']->id);

    expect($this->getJson(route('property_appointment::public.data', $appointment->token))->json('usable'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Calendar console feed
|--------------------------------------------------------------------------
|
| The console renders an appointment's whole detail popover from the calendar
| feed, so a missing field is not a cosmetic problem — it is a blank popover
| with no second request to fall back on. The controller is driven directly
| here: routing it would pull in tenant resolution from the request host,
| which is not what these assertions are about.
|
*/

/** @return array<string, mixed> the first event the calendar feed produces */
function vsFeedEvent(): array
{
    $response = app(App\Http\Controllers\Property\PropertyAppointmentController::class)
        ->calendarData(new Illuminate\Http\Request());

    return json_decode($response->getContent(), true)[0] ?? [];
}

it('feeds the calendar with everything the detail popover renders', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['employee']->id));

    vsGrant($seed['employee'], 'rent out lease.view');
    $this->actingAs($seed['employee']);

    $event = vsFeedEvent();
    $props = $event['extendedProps'];

    // The block must have a real end, or every appointment draws the same
    // default height and the grid stops telling the truth about the day. A
    // booking with no window of its own runs for the configured slot length,
    // whatever the tenant has set that to.
    expect($event['end'])->not->toBeNull()
        ->and(Carbon\Carbon::parse($event['start'])->diffInMinutes(Carbon\Carbon::parse($event['end'])))
        ->toBe((float) SlotService::slotLengthMinutes())
        ->and($props['reference_no'])->toBe($appointment->fresh()->reference_no)
        ->and($props['status_label'])->toBe('Confirmed')
        ->and($props['customer'])->toBe('VS Customer')
        ->and($props['property'])->toBe('VS-101')
        ->and($props['time_range'])->toContain('–')
        ->and($props['long_date'])->not->toBeEmpty()
        ->and($props['agreement_url'])->toContain('/property/sale/view/'.$seed['rentOut']->id)
        ->and($props['agreement_label'])->toBe('Open sale / lease view');
});

it('withholds the agreement link from a user who may not open it', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['employee']->id));

    // No 'rent out lease.view': the popover must not offer a link this user
    // would only be shown a 403 by.
    $this->actingAs($seed['employee']);

    $props = vsFeedEvent()['extendedProps'];

    expect($props['agreement_url'])->toBeNull()
        ->and($props['agreement_label'])->toBeNull()
        ->and($props['reference_no'])->not->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Lifecycle emails
|--------------------------------------------------------------------------
|
| Cancelling and rescheduling tell the customer, but only when there was a
| confirmed appointment to disturb — and neither is ever allowed to fail the
| operation that triggered it.
|
*/

/** A appointment already confirmed on its first free slot. */
function vsBookedAppointment(array $seed): PropertyAppointment
{
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['employee']->id));

    return $appointment->fresh();
}

it('tells the customer when a confirmed appointment is cancelled', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = vsBookedAppointment($seed);

    (new CancelAction())->execute($appointment->id, $seed['employee']->id, 'Employee unavailable');

    $log = EmailLog::where('type', 'appointment_cancelled')->latest('id')->first();

    // The queue runs inline under test, so the row is already 'sent'; what
    // matters is that it did not fail.
    expect($log)->not->toBeNull()
        ->and($log->status)->not->toBe('failed')
        ->and($log->to_email)->toBe($seed['customer']->email)
        ->and($log->related_id)->toBe($appointment->id)
        ->and($log->body)->not->toBeEmpty();
});

it('says nothing when cancelling a appointment the customer never booked', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    (new CancelAction())->execute($appointment->id, $seed['employee']->id);

    expect(EmailLog::where('type', 'appointment_cancelled')->count())->toBe(0);
});

it('still cancels when the tenant has no cancellation template', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = vsBookedAppointment($seed);

    EmailTemplate::where('type', 'appointment_cancelled')->update(['is_active' => 0]);

    $response = (new CancelAction())->execute($appointment->id, $seed['employee']->id);

    // The cancellation stands, and the silence is on the record rather than lost.
    expect($response['success'])->toBeTrue()
        ->and($appointment->fresh()->status)->toBe('cancelled')
        ->and(EmailLog::where('type', 'appointment_cancelled')->where('status', 'failed')->count())->toBe(1);
});

it('tells the customer when a confirmed appointment is moved', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = vsBookedAppointment($seed);

    $slots = app(SlotService::class)->availableSlots($seed['employee']->id);
    $another = collect($slots)->flatten(1)->pluck('value')->first(fn ($value) => $value !== $appointment->scheduled_at->format('Y-m-d H:i:s'));

    (new BookAction())->execute($appointment->id, $another, 'staff', $seed['employee']->id);

    $log = EmailLog::where('type', 'appointment_rescheduled')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->related_id)->toBe($appointment->id)
        ->and($appointment->fresh()->scheduled_at->format('Y-m-d H:i:s'))->toBe($another);
});

it('does not call a first booking a reschedule', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);

    vsBookedAppointment($seed);

    expect(EmailLog::where('type', 'appointment_rescheduled')->count())->toBe(0);
});

it('queues a reminder once, however often the command runs', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = vsBookedAppointment($seed);

    // Wide enough to catch whichever slot the employee's rules produced first.
    $hours = (int) ceil(now()->diffInHours($appointment->scheduled_at, false)) + 1;

    $this->artisan('appointments:send-reminders', ['--hours' => $hours])->assertSuccessful();
    $this->artisan('appointments:send-reminders', ['--hours' => $hours])->assertSuccessful();

    expect(EmailLog::where('type', 'appointment_reminder')->count())->toBe(1)
        ->and($appointment->fresh()->reminder_sent_at)->not->toBeNull();
});

it('leaves appointments outside the reminder window alone', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = vsBookedAppointment($seed);

    $this->artisan('appointments:send-reminders', ['--hours' => 1])->assertSuccessful();

    expect(EmailLog::where('type', 'appointment_reminder')->count())->toBe(0)
        ->and($appointment->fresh()->reminder_sent_at)->toBeNull();
});

it('reminds again about a appointment that was moved after its reminder went out', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = vsBookedAppointment($seed);

    $hours = (int) ceil(now()->diffInHours($appointment->scheduled_at, false)) + 1;
    $this->artisan('appointments:send-reminders', ['--hours' => $hours])->assertSuccessful();

    $slots = app(SlotService::class)->availableSlots($seed['employee']->id);
    $another = collect($slots)->flatten(1)->pluck('value')->first(fn ($value) => $value !== $appointment->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    (new BookAction())->execute($appointment->id, $another, 'staff', $seed['employee']->id);

    expect($appointment->fresh()->reminder_sent_at)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Company hours (Settings -> Working Day)
|--------------------------------------------------------------------------
|
| An employee's own availability is an OVERRIDE, not a prerequisite: with none of
| their own they are bookable on the company week, so nobody has to remember to
| set up a schedule before an appointment link works.
|
*/

/** Write the tenant's working week. $week maps a day name to its hours, or false when closed. */
function vsWorkingWeek(int $tenantId, array $week): void
{
    WorkingDay::query()->where('tenant_id', $tenantId)->delete();

    foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $order => $name) {
        $hours = $week[$name] ?? false;

        WorkingDay::create([
            'tenant_id' => $tenantId,
            'day_name' => $name,
            'is_working' => (bool) $hours,
            'start_time' => $hours['start_time'] ?? null,
            'end_time' => $hours['end_time'] ?? null,
            'order_no' => $order,
        ]);
    }
}

it('offers the company hours to an employee who has none of their own', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();

    vsWorkingWeek($seed['tenant']->id, [
        'Monday' => ['start_time' => '10:00', 'end_time' => '12:00'],
        'Wednesday' => ['start_time' => '10:00', 'end_time' => '12:00'],
    ]);

    $slots = app(SlotService::class)->availableSlots($seed['employee']->id);

    expect($slots)->not->toBeEmpty();

    foreach ($slots as $day => $daySlots) {
        expect(Carbon\Carbon::parse($day)->dayOfWeek)->toBeIn([1, 3]);

        foreach ($daySlots as $slot) {
            $moment = Carbon\Carbon::parse($slot['value']);
            expect((int) $moment->format('G'))->toBeGreaterThanOrEqual(10)->toBeLessThan(12)
                ->and($moment->minute)->toBe(0);
        }
    }

    // No rows were invented on the employee's behalf — the company hours are
    // read live, so editing Settings still moves this employee's slots.
    expect(PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->count())->toBe(0);
});

it('lets an employee\'s own hours override the company hours', function () {
    $seed = vsSeed(); // seeds the employee with 09:00-13:00 every day

    vsWorkingWeek($seed['tenant']->id, [
        'Monday' => ['start_time' => '15:00', 'end_time' => '17:00'],
        'Tuesday' => ['start_time' => '15:00', 'end_time' => '17:00'],
        'Wednesday' => ['start_time' => '15:00', 'end_time' => '17:00'],
        'Thursday' => ['start_time' => '15:00', 'end_time' => '17:00'],
        'Friday' => ['start_time' => '15:00', 'end_time' => '17:00'],
        'Saturday' => ['start_time' => '15:00', 'end_time' => '17:00'],
        'Sunday' => ['start_time' => '15:00', 'end_time' => '17:00'],
    ]);

    $slots = app(SlotService::class)->availableSlots($seed['employee']->id);

    expect($slots)->not->toBeEmpty();

    foreach (collect($slots)->flatten(1) as $slot) {
        $hour = (int) Carbon\Carbon::parse($slot['value'])->format('G');
        expect($hour)->toBeGreaterThanOrEqual(9)->toBeLessThan(13);
    }
});

it('offers nothing when every company day is closed and the employee has no hours', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();

    vsWorkingWeek($seed['tenant']->id, []);

    expect(app(SlotService::class)->availableSlots($seed['employee']->id))->toBeEmpty();
});

it('falls back to the module default week when no working day is configured', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();
    WorkingDay::query()->where('tenant_id', $seed['tenant']->id)->delete();

    $slots = app(SlotService::class)->availableSlots($seed['employee']->id);
    $defaults = config('property_appointment.default_availability');

    expect($slots)->not->toBeEmpty();

    foreach ($slots as $day => $daySlots) {
        expect(Carbon\Carbon::parse($day)->dayOfWeek)->toBeIn($defaults['days']);

        foreach ($daySlots as $slot) {
            $hour = (int) Carbon\Carbon::parse($slot['value'])->format('G');
            expect($hour)->toBeGreaterThanOrEqual((int) substr($defaults['start_time'], 0, 2))
                ->toBeLessThan((int) substr($defaults['end_time'], 0, 2));
        }
    }
});

it('borrows the module times only for the columns a working day leaves blank', function () {
    $seed = vsSeed();

    // A tenant upgraded from before the timing columns existed: the day is on,
    // but it has never been given hours.
    vsWorkingWeek($seed['tenant']->id, ['Monday' => ['start_time' => '08:00']]);

    $schedule = WorkingDay::schedule();
    $defaults = config('property_appointment.default_availability');

    expect($schedule)->toHaveKey(1)
        ->and($schedule[1]['start_time'])->toBe('08:00')
        ->and($schedule[1]['end_time'])->toBe($defaults['end_time']);
});

it('matches working days whatever case the day name was stored in', function () {
    $seed = vsSeed(); // employee works 09:00-13:00 on all seven days

    // The seeder writes day names in upper case; the settings screen shows them
    // capitalised. Both have to filter the week identically.
    WorkingDay::query()->where('tenant_id', $seed['tenant']->id)->delete();
    foreach (['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'] as $order => $name) {
        WorkingDay::create([
            'tenant_id' => $seed['tenant']->id,
            'day_name' => $name,
            'is_working' => $name === 'MONDAY',
            'order_no' => $order,
        ]);
    }

    $slots = app(SlotService::class)->availableSlots($seed['employee']->id);

    expect($slots)->not->toBeEmpty();

    foreach (array_keys($slots) as $day) {
        expect(Carbon\Carbon::parse($day)->dayOfWeek)->toBe(1);
    }
});

it('copies the company hours rather than the module times when the week is filled in one press', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();

    vsWorkingWeek($seed['tenant']->id, [
        'Monday' => ['start_time' => '08:00', 'end_time' => '12:00'],
    ]);

    (new AvailabilityDefaultsAction())->execute($seed['employee']->id, $seed['employee']->id);

    $rules = PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->get();

    expect($rules)->toHaveCount(1)
        ->and((int) $rules->first()->day_of_week)->toBe(1)
        ->and(substr((string) $rules->first()->start_time, 0, 5))->toBe('08:00')
        ->and(substr((string) $rules->first()->end_time, 0, 5))->toBe('12:00');
});

it('books a slot that only the company hours make available', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();

    vsWorkingWeek($seed['tenant']->id, [
        'Sunday' => ['start_time' => '09:00', 'end_time' => '17:00'],
        'Monday' => ['start_time' => '09:00', 'end_time' => '17:00'],
        'Tuesday' => ['start_time' => '09:00', 'end_time' => '17:00'],
        'Wednesday' => ['start_time' => '09:00', 'end_time' => '17:00'],
        'Thursday' => ['start_time' => '09:00', 'end_time' => '17:00'],
        'Friday' => ['start_time' => '09:00', 'end_time' => '17:00'],
        'Saturday' => ['start_time' => '09:00', 'end_time' => '17:00'],
    ]);

    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $slot = vsFirstSlot($seed['employee']->id);

    $response = (new BookAction())->execute($appointment->id, $slot, 'customer');

    expect($response['success'])->toBeTrue()
        ->and($appointment->refresh()->scheduled_at->format('Y-m-d H:i:s'))->toBe($slot);
});

it('saves the company hours from the settings screen', function () {
    $seed = vsSeed();
    vsWorkingWeek($seed['tenant']->id, ['Monday' => ['start_time' => '09:00', 'end_time' => '18:00']]);
    vsGrant($seed['employee'], 'configuration.settings');
    $this->actingAs($seed['employee']);

    $monday = collect(WorkingDay::orderBy('order_no')->get())->firstWhere('day_name', 'Monday');

    Livewire::test(App\Livewire\Settings\WorkingDay::class)
        ->set('days.1.is_working', true)
        ->set('days.1.start_time', '10:30')
        ->set('days.1.end_time', '16:00')
        ->call('updateSettings')
        ->assertDispatched('success');

    $monday->refresh();

    expect(substr((string) $monday->start_time, 0, 5))->toBe('10:30')
        ->and(substr((string) $monday->end_time, 0, 5))->toBe('16:00')
        ->and(WorkingDay::schedule()[1]['start_time'])->toBe('10:30');
});

it('refuses a working day that closes before it opens', function () {
    $seed = vsSeed();
    vsWorkingWeek($seed['tenant']->id, ['Monday' => ['start_time' => '09:00', 'end_time' => '18:00']]);
    vsGrant($seed['employee'], 'configuration.settings');
    $this->actingAs($seed['employee']);

    Livewire::test(App\Livewire\Settings\WorkingDay::class)
        ->set('days.1.is_working', true)
        ->set('days.1.start_time', '17:00')
        ->set('days.1.end_time', '09:00')
        ->call('updateSettings')
        ->assertDispatched('error');

    $monday = collect(WorkingDay::orderBy('order_no')->get())->firstWhere('day_name', 'Monday');

    expect(substr((string) $monday->start_time, 0, 5))->toBe('09:00');
});

it('creates the default working week from the settings screen when the tenant has none', function () {
    $seed = vsSeed();
    WorkingDay::query()->where('tenant_id', $seed['tenant']->id)->delete();
    vsGrant($seed['employee'], 'configuration.settings');
    $this->actingAs($seed['employee']);

    Livewire::test(App\Livewire\Settings\WorkingDay::class)
        ->call('createDefaultWeek')
        ->assertDispatched('success')
        ->assertCount('days', 7);

    $defaults = config('property_appointment.default_availability');

    expect(WorkingDay::count())->toBe(7)
        ->and(WorkingDay::where('is_working', true)->count())->toBe(count($defaults['days']))
        ->and(array_keys(WorkingDay::schedule()))->toBe($defaults['days'])
        ->and(WorkingDay::schedule()[$defaults['days'][0]]['start_time'])->toBe($defaults['start_time']);
});

it('does not duplicate days when the default week is created twice', function () {
    $seed = vsSeed();
    WorkingDay::query()->where('tenant_id', $seed['tenant']->id)->delete();
    vsGrant($seed['employee'], 'configuration.settings');
    $this->actingAs($seed['employee']);

    Livewire::test(App\Livewire\Settings\WorkingDay::class)
        ->call('createDefaultWeek')
        ->call('createDefaultWeek');

    expect(WorkingDay::count())->toBe(7);
});

it('keeps the default week behind the settings permission', function () {
    $seed = vsSeed();
    WorkingDay::query()->where('tenant_id', $seed['tenant']->id)->delete();
    $this->actingAs($seed['employee']);

    Livewire::test(App\Livewire\Settings\WorkingDay::class)
        ->call('createDefaultWeek')
        ->assertForbidden();

    expect(WorkingDay::count())->toBe(0);
});

it('states the company hours on the schedule panel of an employee who has none', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();
    vsWorkingWeek($seed['tenant']->id, [
        'Monday' => ['start_time' => '10:00', 'end_time' => '16:00'],
    ]);
    vsGrant($seed['employee'], 'property appointment.manage availability');
    $this->actingAs($seed['employee']);

    Livewire::test(App\Livewire\PropertyAppointment\EmployeeSchedule::class, ['userId' => $seed['employee']->id])
        ->assertSee('Following the company hours')
        ->assertSee('10:00–16:00')
        ->assertDontSee('No availability set');
});

it('warns on the schedule panel only when there are no hours anywhere', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['employee']->id)->forceDelete();
    vsWorkingWeek($seed['tenant']->id, []);
    $this->actingAs($seed['employee']);

    Livewire::test(App\Livewire\PropertyAppointment\EmployeeSchedule::class, ['userId' => $seed['employee']->id])
        ->assertSee('No availability set');
});

/*
|--------------------------------------------------------------------------
| Typed windows
|--------------------------------------------------------------------------
|
| The public page offers two ways to the same answer: tap a suggested time, or
| type an arriving and a leaving time. Both end as one window, so both go through
| the same gate — a preset is just a window somebody filled in for the customer.
|
*/

/** An appointment on an employee who works 09:00-13:00 every day. */
function vsWindowSeed(): array
{
    $seed = vsSeed();
    $seed['appointment'] = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $seed['day'] = now()->addDays(2)->toDateString();

    return $seed;
}

it('books the window the customer typed, not the configured length', function () {
    $seed = vsWindowSeed();

    $response = (new BookAction())->execute(
        $seed['appointment']->id, $seed['day'].' 10:15:00', 'customer', null, null, $seed['day'].' 10:45:00'
    );

    $booked = $seed['appointment']->fresh();

    expect($response['success'])->toBeTrue()
        ->and($booked->scheduled_at->format('H:i'))->toBe('10:15')
        ->and($booked->ends_at->format('H:i'))->toBe('10:45')
        ->and((int) $booked->scheduled_at->diffInMinutes($booked->endsAt()))->toBe(30);
});

it('falls back to the configured length when no leaving time is given', function () {
    $seed = vsWindowSeed();

    (new BookAction())->execute($seed['appointment']->id, vsFirstSlot($seed['employee']->id));

    $booked = $seed['appointment']->fresh();

    expect((int) $booked->scheduled_at->diffInMinutes($booked->endsAt()))->toBe(SlotService::slotLengthMinutes());
});

it('refuses a window that runs past the day\'s closing time', function () {
    $seed = vsWindowSeed();

    $response = (new BookAction())->execute(
        $seed['appointment']->id, $seed['day'].' 12:30:00', 'customer', null, null, $seed['day'].' 14:00:00'
    );

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('09:00')
        ->and($response['slot_taken'] ?? false)->toBeFalse()
        ->and($seed['appointment']->fresh()->scheduled_at)->toBeNull();
});

it('refuses a window that overlaps an appointment already on the calendar', function () {
    $seed = vsWindowSeed();

    (new BookAction())->execute($seed['appointment']->id, $seed['day'].' 10:00:00', 'customer', null, null, $seed['day'].' 11:00:00');

    $second = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    $response = (new BookAction())->execute($second->id, $seed['day'].' 10:30:00', 'customer', null, null, $seed['day'].' 11:30:00');

    expect($response['success'])->toBeFalse()
        ->and($response['slot_taken'])->toBeTrue()
        ->and($second->fresh()->scheduled_at)->toBeNull();
});

it('allows a window that starts exactly when another finishes', function () {
    $seed = vsWindowSeed();

    (new BookAction())->execute($seed['appointment']->id, $seed['day'].' 10:00:00', 'customer', null, null, $seed['day'].' 11:00:00');

    $second = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];
    $response = (new BookAction())->execute($second->id, $seed['day'].' 11:00:00', 'customer', null, null, $seed['day'].' 12:00:00');

    expect($response['success'])->toBeTrue();
});

it('refuses a window that ends before it starts', function () {
    $seed = vsWindowSeed();

    $response = (new BookAction())->execute(
        $seed['appointment']->id, $seed['day'].' 11:00:00', 'customer', null, null, $seed['day'].' 10:00:00'
    );

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('after the arriving time');
});

it('refuses a window inside the notice period', function () {
    $seed = vsWindowSeed();
    $soon = now()->addHour();

    $response = (new BookAction())->execute(
        $seed['appointment']->id,
        $soon->format('Y-m-d H:i:00'),
        'customer', null, null,
        $soon->copy()->addMinutes(30)->format('Y-m-d H:i:00')
    );

    expect($response['success'])->toBeFalse();
});

it('hands the page the open hours and the taken stretches, not just free slots', function () {
    $seed = vsWindowSeed();
    (new BookAction())->execute($seed['appointment']->id, $seed['day'].' 10:00:00', 'customer', null, null, $seed['day'].' 11:00:00');

    $second = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id, 'employee_id' => $seed['employee']->id], $seed['employee']->id)['data'];

    $payload = json_decode(
        app(App\Http\Controllers\Property\PropertyAppointmentController::class)
            ->publicData($second->token)->getContent(),
        true
    );

    expect($payload['windows'][$seed['day']])->toBe(['start' => '09:00', 'end' => '13:00'])
        ->and($payload['busy'][$seed['day']][0]['start'])->toBe('10:00')
        ->and($payload['busy'][$seed['day']][0]['end'])->toBe('11:00')
        ->and($payload['busy'][$seed['day']][0]['reason'])->toBe('booked')
        ->and($payload['notice_hours'])->toBe(SlotService::minimumNoticeHours())
        ->and($payload['clock'])->toBeIn([12, 24]);
});

it('books a typed window straight through the public endpoint', function () {
    $seed = vsWindowSeed();

    $response = $this->postJson(
        route('property_appointment::public.book', $seed['appointment']->token),
        ['slot' => $seed['day'].' 09:30:00', 'ends_at' => $seed['day'].' 10:15:00', 'timezone' => 'Asia/Qatar']
    );

    $response->assertOk()->assertJson(['success' => true]);

    $booked = $seed['appointment']->fresh();

    expect($booked->scheduled_at->format('H:i'))->toBe('09:30')
        ->and($booked->ends_at->format('H:i'))->toBe('10:15')
        ->and($booked->customer_timezone)->toBe('Asia/Qatar');
});

it('rejects a leaving time that is not after the arriving time at the endpoint', function () {
    $seed = vsWindowSeed();

    $this->postJson(
        route('property_appointment::public.book', $seed['appointment']->token),
        ['slot' => $seed['day'].' 11:00:00', 'ends_at' => $seed['day'].' 10:00:00']
    )->assertStatus(422);
});

it('blocks a window that lands in the employee\'s time off', function () {
    $seed = vsWindowSeed();

    App\Models\PropertyAppointmentTimeOff::create([
        'tenant_id' => $seed['tenant']->id,
        'user_id' => $seed['employee']->id,
        'date' => $seed['day'],
        'start_time' => '10:00',
        'end_time' => '12:00',
        'reason' => 'Training',
    ]);

    $response = (new BookAction())->execute(
        $seed['appointment']->id, $seed['day'].' 11:00:00', 'customer', null, null, $seed['day'].' 11:30:00'
    );

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('unavailable');
});

it('renders the public page shell with the picker mounted', function () {
    $seed = vsWindowSeed();

    $this->get(route('property_appointment::public', $seed['appointment']->token))
        ->assertOk()
        ->assertSee('property-appointment', false)
        ->assertSee('apxp', false);

    // Opening the link is recorded, so staff can tell "never saw it" from
    // "saw it and did not book".
    expect($seed['appointment']->fresh()->link_opened_count)->toBe(1);
});
