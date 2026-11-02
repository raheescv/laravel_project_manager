<?php

use App\Actions\Property\ServiceRequest\CreateAction;
use App\Actions\Property\ServiceRequest\DeleteAction;
use App\Actions\Property\ServiceRequest\StatusChangeAction;
use App\Actions\Property\ServiceRequest\UpdateAction;
use App\Enums\Property\ServiceRequestStatus;
use App\Livewire\Property\ServiceRequest\Page;
use App\Livewire\Property\ServiceRequest\Report;
use App\Livewire\Property\ServiceRequest\Table;
use App\Models\Configuration;
use App\Models\PropertyServiceRequest;
use App\Models\RentOutUtilityTerm;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Utility;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Service requests connect or disconnect a utility on a unit and walk
 * requested → approved → financial approved → completed. Approving a connect
 * on an occupied unit whose rent excludes the utility bills it as a utility term.
 */
beforeEach(function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->tenantId = $user->tenant_id;
    app(TenantService::class)->setCurrentTenant(Tenant::find($this->tenantId));
    session(['branch_id' => 1]);

    $groupId = DB::table('property_groups')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'name' => 'Group '.Str::random(6),
    ]);
    $this->buildingId = DB::table('property_buildings')->insertGetId([
        'tenant_id' => $this->tenantId, 'branch_id' => 1, 'property_group_id' => $groupId, 'name' => 'Tower '.Str::random(6),
    ]);
    $typeId = DB::table('property_types')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => 'Studio '.Str::random(6),
    ]);
    $this->propertyId = DB::table('properties')->insertGetId([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'property_group_id' => $groupId,
        'property_building_id' => $this->buildingId,
        'property_type_id' => $typeId,
        'number' => 'SR-606',
        'ownership' => 'Owner',
        'status' => 'occupied',
    ]);
    $accountId = DB::table('accounts')->insertGetId([
        'tenant_id' => $this->tenantId, 'name' => 'Tenant '.Str::random(6), 'account_type' => 'asset',
    ]);

    $this->wifi = Utility::create(['name' => 'Wifi']);
    $this->ac = Utility::create(['name' => 'AC']);

    $this->rentOutId = DB::table('rent_outs')->insertGetId([
        'tenant_id' => $this->tenantId,
        'branch_id' => 1,
        'property_id' => $this->propertyId,
        'property_group_id' => $groupId,
        'property_building_id' => $this->buildingId,
        'property_type_id' => $typeId,
        'account_id' => $accountId,
        'agreement_type' => 'rental',
        'status' => 'occupied',
        'include_wifi' => 'Excluded',
        'include_ac' => 'Included',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $this->request = fn (array $overrides = []) => (new CreateAction())->execute(array_merge([
        'property_id' => $this->propertyId,
        'utility_id' => $this->wifi->id,
        'type' => 'connect',
        'date' => '2026-03-10',
        'amount' => 300,
    ], $overrides), $user->id);

    $this->advanceTo = function (PropertyServiceRequest $model, ServiceRequestStatus $status): array {
        return (new StatusChangeAction())->execute($model->id, $status, auth()->id());
    };

    $this->grant = fn (string ...$permissions) => auth()->user()->givePermissionTo(
        collect($permissions)->map(fn ($name) => Permission::findOrCreate($name, 'web'))
    );
});

it('records the occupying rent-out and whether its rent includes the utility', function () {
    $response = ($this->request)();

    expect($response['success'])->toBeTrue();
    $model = $response['data']->fresh();
    expect($model->rent_out_id)->toBe($this->rentOutId)
        ->and($model->rent_out_service_status)->toBe('Excluded')
        ->and($model->status)->toBe(ServiceRequestStatus::Requested)
        ->and($model->requested_by)->toBe(auth()->id())
        ->and($model->property_building_id)->toBe($this->buildingId);

    $ac = ($this->request)(['utility_id' => $this->ac->id])['data']->fresh();
    expect($ac->rent_out_service_status)->toBe('Included');
});

it('bills an approved connect as a utility term when the rent excludes the utility', function () {
    $model = ($this->request)()['data'];

    expect(($this->advanceTo)($model, ServiceRequestStatus::Approved)['success'])->toBeTrue();

    $term = RentOutUtilityTerm::where('rent_out_id', $this->rentOutId)->sole();
    expect((float) $term->amount)->toBe(300.0)
        ->and($term->utility_id)->toBe($this->wifi->id)
        ->and($term->date->format('Y-m-d'))->toBe('2026-03-10');
    expect($model->fresh()->approved_by)->toBe(auth()->id());
});

it('does not bill a connect the rent already includes, or a disconnect', function () {
    $included = ($this->request)(['utility_id' => $this->ac->id])['data'];
    $disconnect = ($this->request)(['type' => 'disconnect'])['data'];

    ($this->advanceTo)($included, ServiceRequestStatus::Approved);
    ($this->advanceTo)($disconnect, ServiceRequestStatus::Approved);

    expect(RentOutUtilityTerm::where('rent_out_id', $this->rentOutId)->count())->toBe(0);
});

it('walks the statuses in order and refuses to skip one', function () {
    $model = ($this->request)()['data'];

    $skip = ($this->advanceTo)($model, ServiceRequestStatus::Completed);
    expect($skip['success'])->toBeFalse()
        ->and($skip['message'])->toContain('cannot be moved to Completed');

    foreach ([ServiceRequestStatus::Approved, ServiceRequestStatus::FinancialApproved, ServiceRequestStatus::Completed] as $status) {
        expect(($this->advanceTo)($model, $status)['success'])->toBeTrue();
    }
    $model->refresh();
    expect($model->status)->toBe(ServiceRequestStatus::Completed)
        ->and($model->completed_by)->toBe(auth()->id())
        ->and($model->financial_approved_at)->not->toBeNull();
});

it('locks a completed request against edits and deletion', function () {
    $model = ($this->request)()['data'];
    foreach ([ServiceRequestStatus::Approved, ServiceRequestStatus::FinancialApproved, ServiceRequestStatus::Completed] as $status) {
        ($this->advanceTo)($model, $status);
    }

    $update = (new UpdateAction())->execute(['property_id' => $this->propertyId, 'utility_id' => $this->wifi->id, 'type' => 'connect', 'date' => '2026-03-10', 'amount' => 1], $model->id, auth()->id());
    expect($update['success'])->toBeFalse();
    expect((new DeleteAction())->execute($model->id)['success'])->toBeFalse();
    expect(PropertyServiceRequest::find($model->id))->not->toBeNull();
});

it('pairs each connect with the first disconnect that followed it', function () {
    ($this->request)(['date' => '2026-01-05']);
    ($this->request)(['type' => 'disconnect', 'date' => '2026-02-01', 'remarks' => 'Tenant moved out']);
    ($this->request)(['type' => 'disconnect', 'date' => '2026-02-20', 'remarks' => 'Duplicate']);
    ($this->request)(['date' => '2026-03-01']);

    $periods = PropertyServiceRequest::connectionPeriods()->orderBy('property_service_requests.date')->get();

    expect($periods)->toHaveCount(2)
        ->and($periods[0]->disconnect_date)->toBe('2026-02-01')
        ->and($periods[0]->disconnect_remarks)->toBe('Tenant moved out')
        ->and($periods[1]->disconnect_date)->toBeNull();
});

it('pre-fills the configured connect charge for an occupied unit', function () {
    ($this->grant)('property service request.create');
    Configuration::updateOrCreate(['key' => 'service_request_connect_amount'], ['value' => '250']);

    Livewire::test(Page::class)
        ->set('formData.property_id', $this->propertyId)
        ->assertSet('formData.amount', 250.0)
        ->assertSet('propertyInfo.service_status', 'Excluded')
        ->set('formData.type', 'disconnect')
        ->assertSet('formData.amount', 0);
});

it('creates a request from the form and moves it on with the matching permission', function () {
    ($this->grant)('property service request.create', 'property service request.edit', 'property service request.approve');

    Livewire::test(Page::class)
        ->set('formData.property_id', $this->propertyId)
        ->set('note', 'Owner asked for it')
        ->call('addNote')
        ->call('save')
        ->assertRedirect();

    $model = PropertyServiceRequest::sole();
    expect($model->notes)->toHaveCount(1)
        ->and($model->notes[0]['note'])->toBe('Owner asked for it');

    Livewire::test(Page::class, ['id' => $model->id])
        ->assertSee('Approve')
        ->call('changeStatus');
    expect($model->fresh()->status)->toBe(ServiceRequestStatus::Approved);

    Livewire::test(Page::class, ['id' => $model->id])
        ->call('changeStatus')
        ->assertForbidden();
});

it('renders the list and report for a user who may see them', function () {
    ($this->grant)('property service request.view', 'property service request.report');
    ($this->request)(['date' => '2026-01-05']);

    Livewire::test(Table::class)->set('from_date', '')->assertSee('SR-606');
    Livewire::test(Report::class)->assertSee('SR-606')->assertSee('Connected');
});

it('guards the pages behind the service request permissions', function () {
    $url = fn (string $name) => 'http://localhost'.route("property::service_request::{$name}", [], false);

    $this->get($url('index'))->assertForbidden();

    ($this->grant)('property service request.view');
    $this->get($url('index'))->assertSuccessful()->assertSee('Service Requests');
    $this->get($url('report'))->assertForbidden();
});
