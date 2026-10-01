<?php

use App\Livewire\Property\PropertyLead\Page;
use App\Models\Configuration;
use App\Models\PropertyLead;
use App\Models\PropertyType;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * The lead form carries the accounts fields (company contact person, sub
 * source / status, property requirements) and must open migrated leads whose
 * type and status still hold legacy values.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);

    $permission = config('permission.models.permission');
    foreach (['create', 'edit'] as $action) {
        $this->world->user->givePermissionTo($permission::firstOrCreate(['name' => "property lead.{$action}", 'guard_name' => 'web']));
    }
});

function leadPageCountryId(): int
{
    return (\App\Models\Country::query()->first() ?? \App\Models\Country::create(['name' => 'Qatar']))->id;
}

function leadPageLead(array $attributes = []): PropertyLead
{
    return PropertyLead::create([
        'tenant_id' => test()->world->tenant->id,
        'branch_id' => test()->world->branch->id,
        'name' => 'Lead '.uniqid(),
        'type' => 'Sales',
        'source' => 'Walk-In',
        'status' => 'New Lead',
        ...$attributes,
    ]);
}

it('saves the requirement, company and sub fields on a new lead', function (): void {
    $propertyType = PropertyType::create(['tenant_id' => $this->world->tenant->id, 'name' => 'Villa']);

    Livewire::test(Page::class)
        ->set('formData.name', 'Acme Relocation')
        ->set('formData.mobile', '97455551234')
        ->set('formData.country_id', leadPageCountryId())
        ->set('formData.type', 'Rentout')
        ->set('formData.company_name', 'Acme')
        ->set('formData.company_contact_person', 'Sara Lee')
        ->set('formData.source', 'Exhibition')
        ->set('formData.sub_source', 'Cityscape 2026')
        ->set('formData.status', 'Follow Up')
        ->set('formData.sub_status', 'Waiting on HR')
        ->set('formData.property_type_id', $propertyType->id)
        ->set('formData.rental_type', 'Yearly')
        ->set('formData.budget_min', '120000')
        ->set('formData.budget_max', '180000')
        ->call('save')
        ->assertHasNoErrors();

    expect(PropertyLead::where('name', 'Acme Relocation')->sole())
        ->type->toBe('Rentout')
        ->company_contact_person->toBe('Sara Lee')
        ->sub_source->toBe('Cityscape 2026')
        ->sub_status->toBe('Waiting on HR')
        ->property_type_id->toBe($propertyType->id)
        ->rental_type->toBe('Yearly')
        ->budget_min->toBe('120000.00')
        ->budget_max->toBe('180000.00');
});

it('rejects a budget max below the min', function (): void {
    Livewire::test(Page::class)
        ->set('formData.budget_min', '5000')
        ->set('formData.budget_max', '1000')
        ->call('save')
        ->assertHasErrors(['formData.budget_max' => 'gte']);
});

it('clears the sub source and sub status when their parent changes', function (): void {
    Configuration::create(['tenant_id' => $this->world->tenant->id, 'key' => 'lead_sub_sources', 'value' => json_encode(['Facebook' => ['Ad A', 'Ad B']])]);

    Livewire::test(Page::class)
        ->set('formData.source', 'Facebook')
        ->assertViewHas('subSources', ['Ad A' => 'Ad A', 'Ad B' => 'Ad B'])
        ->set('formData.sub_source', 'Ad A')
        ->set('formData.sub_status', 'Something')
        ->set('formData.source', 'SMS')
        ->assertSet('formData.sub_source', '')
        ->assertViewHas('subSources', [])
        ->set('formData.status', 'Interested')
        ->assertSet('formData.sub_status', '');
});

it('opens a migrated lead with a drifted status and saves it canonical', function (): void {
    $lead = leadPageLead(['type' => 'Rentout', 'status' => 'Low Budget ', 'mobile' => '97455551234', 'country_id' => leadPageCountryId()]);

    Livewire::test(Page::class, ['lead_id' => $lead->id])
        ->assertSet('formData.status', 'Low Budget')
        ->call('save')
        ->assertHasNoErrors();

    expect($lead->fresh()->status)->toBe('Low Budget');
});

it('keeps an unknown legacy status selectable rather than blanking it', function (): void {
    $lead = leadPageLead(['status' => 'Priority']);

    Livewire::test(Page::class, ['lead_id' => $lead->id])
        ->assertSet('formData.status', 'Priority')
        ->assertViewHas('statuses', fn (array $statuses) => ($statuses['Priority'] ?? null) === 'Priority (legacy)');
});

it('shows rental type only for rent out leads and drops it otherwise', function (): void {
    Livewire::test(Page::class)
        ->set('formData.type', 'Rentout')
        ->assertSee('Rental type')
        ->set('formData.rental_type', 'Monthly')
        ->set('formData.type', 'Sales')
        ->assertDontSee('Rental type')
        ->assertSet('formData.rental_type', '');
});

it('shows notes in the system date time format with a relative time', function (): void {
    $this->travelTo(now()->setTime(15, 30));
    $lead = leadPageLead(['remarks' => [
        ['date' => '2026-07-22', 'note' => 'book a visit'],
        ['date' => now()->toDateString(), 'note' => 'call back', 'created_at' => now()->subHours(5)->toDateTimeString()],
    ]]);

    Livewire::test(Page::class, ['lead_id' => $lead->id])
        ->assertSee('22-07-2026')
        ->assertSee('5 hours ago')
        ->assertSee(systemDateTime(now()->subHours(5)));
});

it('shows the change history with field names as columns and readable values', function (): void {
    // Audits normally go through the database queue; write them inline here.
    config(['audit.queue.enable' => false]);
    $lead = leadPageLead(['status' => 'New Lead', 'assigned_to' => $this->world->user->id]);
    $other = \App\Models\User::factory()->create(['name' => 'Second Salesman']);

    $lead->update(['status' => 'Visit Scheduled', 'meeting_time' => '18:00:00', 'location' => 'Site']);
    $lead->update(['meeting_time' => '18:00']);
    $lead->update(['assigned_to' => $other->id]);

    $trail = \App\Support\LeadAuditTrail::for($lead->id);

    expect($trail['columns'])->toHaveKeys(['status', 'assigned_to', 'meeting_time', 'location'])
        ->and($trail['columns']['assigned_to'])->toBe('Assigned to')
        ->and($trail['rows'])->toHaveCount(3)
        ->and($trail['rows'][0]['cells']['assigned_to'])->toBe(['old' => $this->world->user->name, 'new' => 'Second Salesman'])
        ->and($trail['rows'][1]['cells']['status'])->toBe(['old' => 'New Lead', 'new' => 'Visit Scheduled']);

    Livewire::test(Page::class, ['lead_id' => $lead->id])
        ->assertSee('Change history')
        ->assertSee('Assigned to')
        ->assertSee('Second Salesman');
});

it('shows an empty change history rather than hiding it', function (): void {
    $lead = leadPageLead();
    \OwenIt\Auditing\Models\Audit::where('auditable_id', $lead->id)->delete();

    Livewire::test(Page::class, ['lead_id' => $lead->id])
        ->assertSee('Change history')
        ->assertSee('No changes recorded yet');
});

it('offers the configured sub statuses as a dropdown and disables it when there are none', function (): void {
    \App\Support\LeadOptions::save(\App\Support\LeadOptions::SUB_STATUSES, ['Follow Up' => ['Call again', 'Send brochure']]);

    Livewire::test(Page::class)
        ->set('formData.status', 'Follow Up')
        ->assertSeeHtml('<option value="Send brochure">Send brochure</option>')
        ->set('formData.status', 'Interested')
        ->assertSee('None for Interested');
});

it('applies the accounts basics: name, mobile or email, and nationality', function (): void {
    Livewire::test(Page::class)
        ->set('formData.name', '')
        ->set('formData.mobile', '')
        ->set('formData.email', '')
        ->set('formData.country_id', null)
        ->call('save')
        ->assertHasErrors([
            'formData.name' => 'required',
            'formData.mobile' => 'required_without',
            'formData.email' => 'required_without',
            'formData.country_id' => 'required',
        ])
        ->assertSee('The mobile field is required');
});

it('accepts an email alone and rejects a mobile that is not 6-15 digits', function (): void {
    Livewire::test(Page::class)
        ->set('formData.country_id', leadPageCountryId())
        ->set('formData.mobile', '974 5555')
        ->call('save')
        ->assertHasErrors(['formData.mobile' => 'regex'])
        ->set('formData.mobile', '')
        ->set('formData.email', 'lead@example.com')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires a location for a scheduled visit', function (): void {
    Livewire::test(Page::class)
        ->set('formData.mobile', '97455551234')
        ->set('formData.country_id', leadPageCountryId())
        ->set('formData.status', 'Visit Scheduled')
        ->call('save')
        ->assertHasErrors(['formData.location' => 'required_if']);
});

it('starts a new lead with Qatar as the nationality', function (): void {
    $qatar = \App\Models\Country::firstOrCreate(['code' => 'QA'], ['name' => 'Qatar']);

    Livewire::test(Page::class)->assertSet('formData.country_id', $qatar->id);
});
