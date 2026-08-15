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
 * The appointment scheduler's one non-negotiable invariant is that a salesman can
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

    $salesman = User::create([
        'tenant_id' => $tenant->id, 'name' => 'VS Salesman', 'type' => 'employee',
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
        'salesman_id' => $salesman->id, 'property_id' => $property->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id,
        'property_group_id' => $group->id, 'agreement_type' => 'lease',
        'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
        'created_by' => $salesman->id,
    ]);

    foreach (range(0, 6) as $dayOfWeek) {
        PropertyAppointmentAvailability::create([
            'tenant_id' => $tenant->id, 'user_id' => $salesman->id, 'day_of_week' => $dayOfWeek,
            'start_time' => '09:00', 'end_time' => '13:00', 'slot_interval_minutes' => 60,
            'is_active' => true,
        ]);
    }

    return compact('tenant', 'salesman', 'customer', 'rentOut', 'branch', 'property');
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

function vsFirstSlot(int $salesmanId): string
{
    $slots = app(SlotService::class)->availableSlots($salesmanId);
    $day = array_key_first($slots);

    return $slots[$day][0]['value'];
}

it('generates slots only inside the salesman\'s availability', function () {
    $seed = vsSeed();

    $slots = app(SlotService::class)->availableSlots($seed['salesman']->id);

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

    $slots = app(SlotService::class)->availableSlots($seed['salesman']->id);
    $first = collect($slots)->flatten(1)->first();

    // The value is what BookAction and the unique index rely on, so it must
    // stay canonical no matter how the label is displayed.
    expect($first['value'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/')
        ->and($first['label'])->toMatch('/^\d{2}:\d{2} (AM|PM)$/');
});

it('never offers a slot that is already booked', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    $slot = vsFirstSlot($seed['salesman']->id);

    (new BookAction())->execute($appointment->id, $slot);

    $after = app(SlotService::class)->availableSlots($seed['salesman']->id);
    $stillOffered = collect($after)->flatten(1)->contains('value', $slot);

    expect($stillOffered)->toBeFalse();
});

it('refuses to double-book a salesman for the same moment', function () {
    $seed = vsSeed();
    $first = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    $second = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    $slot = vsFirstSlot($seed['salesman']->id);

    expect((new BookAction())->execute($first->id, $slot)['success'])->toBeTrue();

    $response = (new BookAction())->execute($second->id, $slot);

    expect($response['success'])->toBeFalse()
        ->and(PropertyAppointment::where('status', 'scheduled')->where('scheduled_at', $slot)->count())->toBe(1);
});

it('releases the slot again when a appointment is cancelled', function () {
    $seed = vsSeed();
    $first = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    $second = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    $slot = vsFirstSlot($seed['salesman']->id);

    (new BookAction())->execute($first->id, $slot);
    (new CancelAction())->execute($first->id, $seed['salesman']->id, 'testing');

    expect((new BookAction())->execute($second->id, $slot)['success'])->toBeTrue();
});

it('will not open a appointment on an agreement with no salesman', function () {
    $seed = vsSeed();
    $seed['rentOut']->update(['salesman_id' => null]);

    $response = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], 1);

    expect($response['success'])->toBeFalse()
        ->and($response['message'])->toContain('no salesman');
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
    $this->actingAs($seed['salesman']);

    Livewire::test(AppointmentTab::class, ['rentOutId' => $seed['rentOut']->id])
        ->assertOk()
        ->assertSee('No appointment link sent yet');
});

it('tells the user why the tab is unusable with no salesman', function () {
    $seed = vsSeed();
    $seed['rentOut']->update(['salesman_id' => null]);
    $this->actingAs($seed['salesman']);

    Livewire::test(AppointmentTab::class, ['rentOutId' => $seed['rentOut']->id])
        ->assertOk()
        ->assertSee('No salesman on this agreement');
});

it('serves the public appointment page for a valid token', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

    $response = $this->getJson(route('property_appointment::public.data', $appointment->token))->assertOk();

    $payload = $response->json();

    expect($payload['status'])->toBe('awaiting')
        ->and($payload['customer_name'])->toBe('VS Customer')
        ->and($payload['salesman_name'])->toBe('VS Salesman')
        ->and(count($payload['days']))->toBeGreaterThan(1)
        ->and($payload['days'][0]['slots'][0])->toHaveKeys(['value', 'label']);
});

it('books through the public endpoint and records who booked it', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    $slot = vsFirstSlot($seed['salesman']->id);

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
    $first = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    $second = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    $slot = vsFirstSlot($seed['salesman']->id);

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

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

    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['salesman']->id));

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

it('fills the whole default week for a salesman in one press', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['salesman']->id)->forceDelete();

    $response = (new AvailabilityDefaultsAction())->execute($seed['salesman']->id, $seed['salesman']->id);

    expect($response['success'])->toBeTrue()
        ->and(PropertyAppointmentAvailability::where('user_id', $seed['salesman']->id)->count())
        ->toBe(count(config('property_appointment.default_availability.days')));
});

it('uses the tenant working days rather than the config fallback when they exist', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['salesman']->id)->forceDelete();

    // Only Monday and Wednesday are worked here.
    foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $order => $name) {
        WorkingDay::create([
            'tenant_id' => $seed['tenant']->id,
            'day_name' => $name,
            'is_working' => in_array($name, ['Monday', 'Wednesday'], true),
            'order_no' => $order,
        ]);
    }

    (new AvailabilityDefaultsAction())->execute($seed['salesman']->id, $seed['salesman']->id);

    $days = PropertyAppointmentAvailability::where('user_id', $seed['salesman']->id)
        ->pluck('day_of_week')->map(fn ($d) => (int) $d)->sort()->values()->all();

    expect($days)->toBe([1, 3]);
});

it('does not duplicate or overwrite hours when pressed twice', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['salesman']->id)->forceDelete();

    (new AvailabilityDefaultsAction())->execute($seed['salesman']->id, $seed['salesman']->id);

    $first = PropertyAppointmentAvailability::where('user_id', $seed['salesman']->id)->first();
    $first->update(['start_time' => '11:00', 'end_time' => '15:00']);
    $countAfterFirst = PropertyAppointmentAvailability::where('user_id', $seed['salesman']->id)->count();

    $second = (new AvailabilityDefaultsAction())->execute($seed['salesman']->id, $seed['salesman']->id);

    expect($second['success'])->toBeFalse()
        ->and(PropertyAppointmentAvailability::where('user_id', $seed['salesman']->id)->count())->toBe($countAfterFirst)
        ->and(substr((string) $first->refresh()->start_time, 0, 5))->toBe('11:00');
});

it('produces bookable slots straight after the default week is applied', function () {
    $seed = vsSeed();
    PropertyAppointmentAvailability::where('user_id', $seed['salesman']->id)->forceDelete();

    (new AvailabilityDefaultsAction())->execute($seed['salesman']->id, $seed['salesman']->id);

    $slots = app(SlotService::class)->availableSlots($seed['salesman']->id);

    expect($slots)->not->toBeEmpty();
});

it('logs a sent email with the body the recipient actually received', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

    $response = (new SendLinkAction())->execute($appointment->id, 'appointment_invite', $seed['salesman']->id);

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

    (new SendLinkAction())->execute($appointment->id, 'appointment_invite', $seed['salesman']->id);
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

    $this->actingAs($seed['salesman'])
        ->get(route('log::emails'))
        ->assertForbidden();
});

it('injects the styled call-to-action instead of printing its markup', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['salesman']->id));

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

    $variables = app(AppointmentMailData::class)->forAppointment($appointment);

    expect($variables['unit_number'])->toBe('VS-101')
        ->and($variables['building_name'])->toBe('VS Building')
        ->and($variables['project_name'])->toBe('VS Group');
});

it('renders the unit inside its building in the invitation', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

    // Nothing is configured in Company Profile for this tenant.
    expect(app(AppointmentMailData::class)->forAppointment($appointment)['company_name'])->not->toBeEmpty();
});

it('reflects a status change without needing a page refresh', function () {
    $seed = vsSeed();
    vsGrant($seed['salesman'], 'property appointment.edit');
    $this->actingAs($seed['salesman']);
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['salesman']->id));

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['salesman']->id));
    (new StatusAction())->execute($appointment->id, 'no_show', $seed['salesman']->id);

    $payload = $this->getJson(route('property_appointment::public.data', $appointment->token))->assertOk()->json();

    expect($payload['status'])->toBe('no_show')
        ->and($payload['usable'])->toBeTrue()
        ->and($payload['days'])->not->toBeEmpty();

    $this->postJson(route('property_appointment::public.book', $appointment->token), ['slot' => vsFirstSlot($seed['salesman']->id)])
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'scheduled']);
});

it('lets a cancelled customer book again from the same link', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['salesman']->id));
    (new CancelAction())->execute($appointment->id, $seed['salesman']->id, 'testing');

    $this->postJson(route('property_appointment::public.book', $appointment->token), ['slot' => vsFirstSlot($seed['salesman']->id)])
        ->assertOk()
        ->assertJson(['success' => true, 'status' => 'scheduled']);
});

it('closes the link once the visit is completed', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['salesman']->id));
    (new StatusAction())->execute($appointment->id, 'completed', $seed['salesman']->id);

    $payload = $this->getJson(route('property_appointment::public.data', $appointment->token))->assertOk()->json();

    expect($payload['usable'])->toBeFalse()
        ->and($payload['days'])->toBeEmpty();
});

it('still lets staff revoke a link deliberately', function () {
    $seed = vsSeed();
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

    (new RevokeLinkAction())->execute($appointment->id, $seed['salesman']->id);

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['salesman']->id));

    vsGrant($seed['salesman'], 'rent out lease.view');
    $this->actingAs($seed['salesman']);

    $event = vsFeedEvent();
    $props = $event['extendedProps'];

    // The block must have a real end, or every appointment draws the same
    // default height and the grid stops telling the truth about the day.
    expect($event['end'])->not->toBeNull()
        ->and(Carbon\Carbon::parse($event['start'])->diffInMinutes(Carbon\Carbon::parse($event['end'])))->toBe(60.0)
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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['salesman']->id));

    // No 'rent out lease.view': the popover must not offer a link this user
    // would only be shown a 403 by.
    $this->actingAs($seed['salesman']);

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];
    (new BookAction())->execute($appointment->id, vsFirstSlot($seed['salesman']->id));

    return $appointment->fresh();
}

it('tells the customer when a confirmed appointment is cancelled', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = vsBookedAppointment($seed);

    (new CancelAction())->execute($appointment->id, $seed['salesman']->id, 'Salesman unavailable');

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
    $appointment = (new CreateAction())->execute(['rent_out_id' => $seed['rentOut']->id], $seed['salesman']->id)['data'];

    (new CancelAction())->execute($appointment->id, $seed['salesman']->id);

    expect(EmailLog::where('type', 'appointment_cancelled')->count())->toBe(0);
});

it('still cancels when the tenant has no cancellation template', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = vsBookedAppointment($seed);

    EmailTemplate::where('type', 'appointment_cancelled')->update(['is_active' => 0]);

    $response = (new CancelAction())->execute($appointment->id, $seed['salesman']->id);

    // The cancellation stands, and the silence is on the record rather than lost.
    expect($response['success'])->toBeTrue()
        ->and($appointment->fresh()->status)->toBe('cancelled')
        ->and(EmailLog::where('type', 'appointment_cancelled')->where('status', 'failed')->count())->toBe(1);
});

it('tells the customer when a confirmed appointment is moved', function () {
    $seed = vsSeed();
    (new CreateDefaultsAction())->execute(AppointmentMailData::MODULE);
    $appointment = vsBookedAppointment($seed);

    $slots = app(SlotService::class)->availableSlots($seed['salesman']->id);
    $another = collect($slots)->flatten(1)->pluck('value')->first(fn ($value) => $value !== $appointment->scheduled_at->format('Y-m-d H:i:s'));

    (new BookAction())->execute($appointment->id, $another, 'staff', $seed['salesman']->id);

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

    // Wide enough to catch whichever slot the salesman's rules produced first.
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

    $slots = app(SlotService::class)->availableSlots($seed['salesman']->id);
    $another = collect($slots)->flatten(1)->pluck('value')->first(fn ($value) => $value !== $appointment->fresh()->scheduled_at->format('Y-m-d H:i:s'));
    (new BookAction())->execute($appointment->id, $another, 'staff', $seed['salesman']->id);

    expect($appointment->fresh()->reminder_sent_at)->toBeNull();
});
