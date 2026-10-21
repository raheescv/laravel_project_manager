<?php

use App\Actions\RentOut\Checklist\GeneratePdfAction;
use App\Livewire\RentOut\Checklist\Sign;
use App\Livewire\RentOut\Tabs\ChecklistTab;
use App\Models\Account;
use App\Models\Checklist;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Models\RentOut;
use App\Models\RentOutChecklistLine;
use App\Models\RentOutChecklistSignature;
use App\Models\RentOutFixtureArea;
use App\Models\RentOutFixtureEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The technician app works the RentOut move-in / move-out checklist through
 * /api/v1/technician/checklists. A user only reaches the rent-outs they
 * coordinate, statuses follow the same per-phase rules as the web tab, each
 * coordinator signs only their own role, and a phase seals once all three
 * signatories have signed.
 */
beforeEach(function (): void {
    Storage::fake('public');

    $this->world = PosWorld::create();
    $this->me = $this->world->user;
    $this->colleague = checklistUser($this->world, 'Sara Mathew');

    $this->rentOut = checklistRentOut($this->world, facility: $this->me, leasing: $this->colleague);
    [$this->fridge, $this->hood, $this->sofa] = checklistLines($this->rentOut, [
        ['Kitchen', 'Refrigerator'],
        ['Kitchen', 'Cooker hood'],
        ['Living Room', 'Sofa'],
    ]);

    Sanctum::actingAs($this->me);
});

function checklistUser(PosWorld $world, string $name): User
{
    return User::factory()->create(['tenant_id' => $world->tenant->id, 'name' => $name, 'default_branch_id' => $world->branch->id]);
}

function checklistRentOut(PosWorld $world, ?User $facility, ?User $leasing, array $overrides = []): RentOut
{
    $tenantId = $world->tenant->id;
    $group = PropertyGroup::firstOrCreate(['tenant_id' => $tenantId, 'name' => 'The Pearl']);
    $building = PropertyBuilding::firstOrCreate(['tenant_id' => $tenantId, 'name' => 'Porto Arabia'], ['branch_id' => $world->branch->id, 'property_group_id' => $group->id]);
    $type = PropertyType::firstOrCreate(['tenant_id' => $tenantId, 'name' => 'Apartment']);
    $property = Property::create([
        'tenant_id' => $tenantId, 'branch_id' => $world->branch->id, 'property_group_id' => $group->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => uniqid('U'),
    ]);
    $lessee = Account::firstOrCreate(['tenant_id' => $tenantId, 'account_type' => 'asset', 'name' => 'Leena Varghese', 'mobile' => '55512345']);

    return RentOut::create(array_merge([
        'tenant_id' => $tenantId, 'branch_id' => $world->branch->id, 'account_id' => $lessee->id,
        'property_id' => $property->id, 'property_building_id' => $building->id, 'property_type_id' => $type->id,
        'property_group_id' => $group->id, 'agreement_type' => 'rental', 'status' => 'booked',
        'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
        'facility_coordinator_id' => $facility?->id, 'leasing_coordinator_id' => $leasing?->id,
        'created_by' => $world->user->id,
    ], $overrides));
}

/**
 * @param  array<int, array{0: string, 1: string}>  $items  [category, name]
 * @return array<int, RentOutChecklistLine>
 */
function checklistLines(RentOut $rentOut, array $items): array
{
    return collect($items)->values()->map(function (array $item, int $i) use ($rentOut) {
        $master = Checklist::create(['tenant_id' => $rentOut->tenant_id, 'category' => $item[0], 'name' => $item[1].' '.uniqid()]);

        return RentOutChecklistLine::create([
            'tenant_id' => $rentOut->tenant_id, 'rent_out_id' => $rentOut->id, 'checklist_id' => $master->id,
            'qty' => 1, 'sort_order' => $i + 1,
        ]);
    })->all();
}

function checklistUrl(PosWorld $world, string $path): string
{
    return $world->url('/api/v1/technician/'.ltrim($path, '/'));
}

function signatureDataUri(): string
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

    return 'data:image/png;base64,'.base64_encode($png);
}

function signAll(RentOut $rentOut, string $phase): void
{
    foreach (['facility_coordinator', 'lessee', 'leasing_coordinator'] as $role) {
        RentOutChecklistSignature::create([
            'tenant_id' => $rentOut->tenant_id, 'rent_out_id' => $rentOut->id, 'phase' => $phase, 'role' => $role,
            'signer_name' => $role, 'signature_path' => "rent-out-checklists/{$rentOut->id}/{$role}.png", 'signed_at' => now(),
        ]);
    }
}

it('lists only the hand-overs the user coordinates', function (): void {
    $leasingSide = checklistRentOut($this->world, facility: $this->colleague, leasing: $this->me);
    checklistLines($leasingSide, [['Kitchen', 'Oven']]);

    $notMine = checklistRentOut($this->world, facility: $this->colleague, leasing: $this->colleague);
    checklistLines($notMine, [['Kitchen', 'Oven']]);

    checklistRentOut($this->world, facility: $this->me, leasing: $this->colleague); // no checklist prepared

    $cancelled = checklistRentOut($this->world, facility: $this->me, leasing: null, overrides: ['status' => 'cancelled']);
    checklistLines($cancelled, [['Kitchen', 'Oven']]);

    $jobs = $this->getJson(checklistUrl($this->world, 'checklists'))->assertSuccessful()->json('data');

    expect(collect($jobs)->pluck('id')->sort()->values()->all())->toBe(collect([$this->rentOut->id, $leasingSide->id])->sort()->values()->all());

    $job = collect($jobs)->firstWhere('id', $this->rentOut->id);
    expect($job)
        ->phase->toBe('move_in')
        ->lessee_name->toBe('Leena Varghese')
        ->lines_total->toBe(3)
        ->lines_checked->toBe(0)
        ->signatures_required->toBe(3)
        ->and($job['my_roles'])->toBe([['role' => 'facility_coordinator', 'label' => 'Facility Coordinator']]);
});

it('hides a checklist the user does not coordinate', function (): void {
    $notMine = checklistRentOut($this->world, facility: $this->colleague, leasing: null);
    [$line] = checklistLines($notMine, [['Kitchen', 'Oven']]);

    $this->getJson(checklistUrl($this->world, "checklists/{$notMine->id}"))->assertNotFound();
    $this->patchJson(checklistUrl($this->world, "checklists/{$notMine->id}/lines/{$line->id}"), ['phase' => 'move_in', 'status' => 'ok'])->assertNotFound();

    expect($line->fresh()->move_in_status)->toBeNull();
});

it('keeps move-in binary: present or nothing', function (): void {
    $url = checklistUrl($this->world, "checklists/{$this->rentOut->id}/lines/{$this->fridge->id}");

    $this->patchJson($url, ['phase' => 'move_in', 'status' => 'ok', 'comment' => ' Door seal worn '])
        ->assertSuccessful()
        ->assertJsonPath('data.lines_checked', 1);
    expect($this->fridge->fresh())
        ->move_in_status->value->toBe('ok')
        ->move_in_comment->toBe('Door seal worn');

    // Damaged is not a move-in state on the web form, so it records nothing.
    $this->patchJson($url, ['phase' => 'move_in', 'status' => 'not_ok'])->assertSuccessful();
    expect($this->fridge->fresh())
        ->move_in_status->toBeNull()
        ->move_in_comment->toBe('Door seal worn');
});

it('records a damaged move-out item and its damage cost', function (): void {
    $url = checklistUrl($this->world, "checklists/{$this->rentOut->id}/lines/{$this->hood->id}");

    $this->patchJson($url, ['phase' => 'move_out', 'status' => 'not_ok', 'damage_cost' => 180, 'comment' => 'Filter clogged'])
        ->assertSuccessful()
        ->assertJsonPath('data.phase', 'move_out')
        ->assertJsonPath('data.lines_damaged', 1)
        ->assertJsonPath('data.damage_total', 180);

    // Marked good again: no damage left to charge.
    $this->patchJson($url, ['phase' => 'move_out', 'status' => 'ok'])->assertSuccessful()->assertJsonPath('data.damage_total', 0);
    expect((float) $this->hood->fresh()->damage_cost)->toBe(0.0);
});

it('marks the rest of a room good without overriding a damaged item', function (): void {
    $this->hood->update(['move_out_status' => 'not_ok']);

    $this->postJson(checklistUrl($this->world, "checklists/{$this->rentOut->id}/lines/mark-ok"), [
        'phase' => 'move_out',
        'line_ids' => [$this->fridge->id, $this->hood->id],
    ])->assertSuccessful();

    expect($this->fridge->fresh()->move_out_status->value)->toBe('ok')
        ->and($this->hood->fresh()->move_out_status->value)->toBe('not_ok')
        ->and($this->sofa->fresh()->move_out_status)->toBeNull();
});

it('keeps the move-in photo when the move-out photo is taken', function (): void {
    $this->fridge->update(['image_path' => 'rent-out-checklist/old-move-in.jpg']);

    $this->post(checklistUrl($this->world, "checklists/{$this->rentOut->id}/lines/{$this->fridge->id}/photo"), [
        'phase' => 'move_out',
        'photo' => UploadedFile::fake()->image('fridge.jpg'),
    ], ['Accept' => 'application/json'])->assertSuccessful();

    $line = $this->fridge->fresh();
    expect($line->image_path)->toBe('rent-out-checklist/old-move-in.jpg')
        ->and($line->move_out_image_path)->toStartWith("rent-out-checklist/{$this->rentOut->id}/");
    Storage::disk('public')->assertExists($line->move_out_image_path);
});

it('lets a coordinator sign their own role and the lessee, but not their colleague', function (): void {
    $url = checklistUrl($this->world, "checklists/{$this->rentOut->id}/signatures");

    $this->postJson($url, ['phase' => 'move_in', 'role' => 'facility_coordinator', 'signature' => signatureDataUri()])->assertSuccessful();
    $signed = $this->postJson($url, ['phase' => 'move_in', 'role' => 'lessee', 'signature' => signatureDataUri()])
        ->assertSuccessful()
        ->assertJsonPath('data.signatures_done', 2);

    expect(collect($signed->json('data.signatures'))->firstWhere('role', 'lessee')['signed_at'])
        ->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');

    $this->postJson($url, ['phase' => 'move_in', 'role' => 'leasing_coordinator', 'signature' => signatureDataUri()])->assertForbidden();

    $mine = RentOutChecklistSignature::where('rent_out_id', $this->rentOut->id)->where('role', 'facility_coordinator')->first();
    $lessee = RentOutChecklistSignature::where('rent_out_id', $this->rentOut->id)->where('role', 'lessee')->first();
    expect($mine->user_id)->toBe($this->me->id)
        ->and($lessee->user_id)->toBeNull()
        ->and($lessee->signer_name)->toBe('Leena Varghese')
        ->and(RentOutChecklistSignature::where('role', 'leasing_coordinator')->exists())->toBeFalse();
});

it('rejects a signature that is not a PNG data uri', function (): void {
    $this->postJson(checklistUrl($this->world, "checklists/{$this->rentOut->id}/signatures"), [
        'phase' => 'move_in', 'role' => 'lessee', 'signature' => 'not-an-image',
    ])->assertUnprocessable()->assertJsonValidationErrors('signature');
});

it('seals a phase only once all three have signed', function (): void {
    $seal = checklistUrl($this->world, "checklists/{$this->rentOut->id}/seal");

    $this->postJson($seal, ['phase' => 'move_in', 'remarks' => 'Keys handed over'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'The Move-In hand-over still needs 3 signature(s) before it can be sealed.');

    signAll($this->rentOut, 'move_in');

    $this->postJson($seal, ['phase' => 'move_in', 'remarks' => 'Keys handed over'])
        ->assertSuccessful()
        ->assertJsonPath('data.sealed', true)
        ->assertJsonPath('data.actual_date', now()->toDateString())
        ->assertJsonPath('data.remarks', 'Keys handed over');

    // Sealed and the tenant is not leaving yet: nothing left in the inbox.
    expect($this->getJson(checklistUrl($this->world, 'checklists'))->json('data'))->toBe([]);
});

it('opens the move-out phase once the move-in is sealed and the tenant is leaving', function (): void {
    signAll($this->rentOut, 'move_in');
    $this->rentOut->update(['actual_move_in_date' => now()->subYear()->toDateString(), 'vacate_date' => now()->addWeek()->toDateString()]);

    $jobs = $this->getJson(checklistUrl($this->world, 'checklists'))->assertSuccessful()->json('data');

    expect($jobs)->toHaveCount(1)
        ->and($jobs[0]['phase'])->toBe('move_out')
        ->and($jobs[0]['scheduled_date'])->toBe(now()->addWeek()->toDateString());
});

it('labels the staff roles by agreement type', function (): void {
    $this->rentOut->update(['agreement_type' => 'lease']);

    $signatures = $this->getJson(checklistUrl($this->world, "checklists/{$this->rentOut->id}"))->assertSuccessful()->json('data.signatures');

    expect(collect($signatures)->pluck('label')->all())->toBe(['Site Engineer', 'Lessee', 'Admin Coordinator'])
        ->and(collect($signatures)->pluck('can_sign')->all())->toBe([true, true, false])
        ->and($signatures[2]['assignee_name'])->toBe('Sara Mathew');
});

it('runs a fixture comment through to the owner accepting the area', function (): void {
    $detail = $this->postJson(checklistUrl($this->world, "checklists/{$this->rentOut->id}/fixtures"), [
        'phase' => 'move_in', 'category' => 'Kitchen', 'comments' => 'Re-grout the splashback',
    ])->assertSuccessful()->json('data');

    $kitchen = collect($detail['fixtures'])->firstWhere('category', 'Kitchen');
    $entryId = $kitchen['entries'][0]['id'];
    expect($kitchen['ready_for_acceptance'])->toBeFalse()
        ->and($kitchen['entries'][0]['status'])->toBe('pending');

    $sign = checklistUrl($this->world, "checklists/{$this->rentOut->id}/fixtures/{$kitchen['id']}/sign");
    $this->postJson($sign, ['phase' => 'move_in', 'owner_name' => 'Owner', 'signature' => signatureDataUri()])
        ->assertUnprocessable();

    $this->post(checklistUrl($this->world, "fixture-entries/{$entryId}/photo"), [
        'phase' => 'move_in', 'which' => 'after', 'photo' => UploadedFile::fake()->image('after.jpg'),
    ], ['Accept' => 'application/json'])->assertSuccessful();

    $done = $this->patchJson(checklistUrl($this->world, "fixture-entries/{$entryId}"), ['phase' => 'move_in', 'status' => 'completed'])
        ->assertSuccessful()->json('data');
    $kitchen = collect($done['fixtures'])->firstWhere('category', 'Kitchen');
    expect($kitchen['ready_for_acceptance'])->toBeTrue()
        ->and($kitchen['entries'][0]['completed_date'])->toBe(now()->toDateString())
        ->and($kitchen['entries'][0]['after_image'])->toStartWith('/storage/rent-out-fixtures/');

    $signed = $this->postJson($sign, ['phase' => 'move_in', 'owner_name' => 'Owner', 'signature' => signatureDataUri()])
        ->assertSuccessful()->json('data');
    expect(collect($signed['fixtures'])->firstWhere('category', 'Kitchen')['owner_name'])->toBe('Owner');

    $this->deleteJson(checklistUrl($this->world, "fixture-entries/{$entryId}?phase=move_in"))->assertSuccessful();
    expect(collect($this->getJson(checklistUrl($this->world, "checklists/{$this->rentOut->id}"))->json('data.fixtures'))->firstWhere('category', 'Kitchen')['entries'])->toBe([]);
});

it('will not touch a fixture on a rent-out the user does not coordinate', function (): void {
    $notMine = checklistRentOut($this->world, facility: $this->colleague, leasing: null);
    checklistLines($notMine, [['Kitchen', 'Oven']]);
    Sanctum::actingAs($this->colleague);
    $entryId = $this->postJson(checklistUrl($this->world, "checklists/{$notMine->id}/fixtures"), ['phase' => 'move_in', 'category' => 'Kitchen'])
        ->assertSuccessful()->json('data.fixtures.0.entries.0.id');

    Sanctum::actingAs($this->me);
    $this->patchJson(checklistUrl($this->world, "fixture-entries/{$entryId}"), ['phase' => 'move_in', 'status' => 'completed'])->assertNotFound();
    $this->deleteJson(checklistUrl($this->world, "fixture-entries/{$entryId}?phase=move_in"))->assertNotFound();
});

it('shows the app’s move-out photo on the web checklist tab', function (): void {
    $this->fridge->update(['move_out_image_path' => 'rent-out-checklist/fridge-out.jpg']);
    $this->actingAs($this->me);

    Livewire::test(ChecklistTab::class, ['rentOutId' => $this->rentOut->id])
        ->assertSeeHtml('storage/rent-out-checklist/fridge-out.jpg')
        ->assertSeeHtml('alt="Move-out"');
});

it('treats a lease as one hand-over with no move-out', function (): void {
    $this->rentOut->update(['agreement_type' => 'lease', 'vacate_date' => now()->addDays(5)->toDateString()]);

    $job = $this->getJson(checklistUrl($this->world, 'checklists'))->assertSuccessful()->json('data.0');
    expect($job)
        ->phase->toBe('move_in')
        ->phase_label->toBe('Handover')
        ->phases->toBe(['move_in']);

    $this->patchJson(checklistUrl($this->world, "checklists/{$this->rentOut->id}/lines/{$this->fridge->id}"), ['phase' => 'move_out', 'status' => 'not_ok'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phase', 'data');
    expect($this->fridge->fresh()->move_out_status)->toBeNull();

    // Sealing the hand-over records the Hand Over Date (and the Inspection Date, still empty).
    signAll($this->rentOut, 'move_in');
    $this->postJson(checklistUrl($this->world, "checklists/{$this->rentOut->id}/seal"), ['phase' => 'move_in', 'actual_date' => now()->subDay()->toDateString()])
        ->assertSuccessful()
        ->assertJsonPath('data.sealed', true)
        ->assertJsonPath('data.actual_date', now()->subDay()->toDateString());

    expect($this->rentOut->fresh())
        ->actual_move_out_date->toDateString()->toBe(now()->subDay()->toDateString())
        ->actual_move_in_date->toDateString()->toBe(now()->subDay()->toDateString())
        ->and($this->getJson(checklistUrl($this->world, 'checklists'))->json('data'))->toBe([]);
});

it('lists completed hand-overs and filters them by hand-over date', function (): void {
    // Move-in sealed last month, tenant leaving next week: one completed job, one open.
    signAll($this->rentOut, 'move_in');
    $this->rentOut->update([
        'actual_move_in_date' => now()->subMonth()->toDateString(),
        'vacate_date' => now()->addWeek()->toDateString(),
    ]);
    $url = fn (array $query) => checklistUrl($this->world, 'checklists?'.http_build_query($query));

    $open = $this->getJson($url([]))->json('data');
    expect(collect($open)->pluck('phase')->all())->toBe(['move_out']);

    $completed = $this->getJson($url(['status' => 'completed']))->json('data');
    expect($completed)->toHaveCount(1)
        ->and($completed[0])->phase->toBe('move_in')->sealed->toBeTrue()
        ->scheduled_date->toBe(now()->subMonth()->toDateString());

    $all = $this->getJson($url(['status' => 'all']))->json('data');
    expect(collect($all)->pluck('phase')->all())->toBe(['move_in', 'move_out']);

    $moveInsLastMonth = $this->getJson($url([
        'status' => 'all', 'date_basis' => 'move_in',
        'from_date' => now()->subMonth()->startOfMonth()->toDateString(), 'to_date' => now()->subMonth()->endOfMonth()->toDateString(),
    ]))->json('data');
    expect(collect($moveInsLastMonth)->pluck('phase')->all())->toBe(['move_in']);

    $moveOutsThisWeek = $this->getJson($url([
        'status' => 'all', 'date_basis' => 'move_out',
        'from_date' => now()->toDateString(), 'to_date' => now()->addDays(10)->toDateString(),
    ]))->json('data');
    expect(collect($moveOutsThisWeek)->pluck('phase')->all())->toBe(['move_out']);

    $this->getJson($url(['status' => 'all', 'date_basis' => 'move_out', 'from_date' => now()->addMonth()->toDateString()]))
        ->assertSuccessful()
        ->assertJsonPath('data', []);
});

it('takes and removes a move-out photo on the web tab straight away', function (): void {
    $this->me->givePermissionTo(Permission::findOrCreate('rent out checklist.edit', 'web'));
    $this->actingAs($this->me);
    $index = 0; // the fridge, first row

    $tab = Livewire::test(ChecklistTab::class, ['rentOutId' => $this->rentOut->id])
        ->set("newMoveOutImages.{$index}", UploadedFile::fake()->image('fridge-out.jpg'))
        ->assertHasNoErrors();

    $path = $this->fridge->fresh()->move_out_image_path;
    expect($path)->toStartWith("rent-out-checklist/{$this->rentOut->id}/");
    Storage::disk('public')->assertExists($path);

    $tab->call('removeMoveOutImage', $index);
    expect($this->fridge->fresh()->move_out_image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('shows each hand-over\'s signatures and seals it from the web tab', function (): void {
    $this->me->givePermissionTo(Permission::findOrCreate('rent out checklist.edit', 'web'));
    $this->actingAs($this->me);

    $tab = Livewire::test(ChecklistTab::class, ['rentOutId' => $this->rentOut->id])
        ->assertSee('Move-In hand-over')
        ->assertSee('Move-Out hand-over')
        ->assertSee('0/3 signed')
        ->call('seal', 'move_in')
        ->assertDispatched('error');
    expect($this->rentOut->fresh()->actual_move_in_date)->toBeNull();

    signAll($this->rentOut, 'move_in');
    $tab->set('moveInRemarks', 'Keys handed over')
        ->call('seal', 'move_in')
        ->assertDispatched('success')
        ->assertSee('Sealed');

    expect($this->rentOut->fresh())
        ->actual_move_in_date->toDateString()->toBe(now()->toDateString())
        ->move_in_remarks->toBe('Keys handed over');
});

it('downloads the checklist PDF, only for a coordinator', function (): void {
    $this->mock(GeneratePdfAction::class)
        ->shouldReceive('execute')->once()->with($this->rentOut->id)->andReturn('%PDF-1.7 fake');

    $response = $this->get(checklistUrl($this->world, "checklists/{$this->rentOut->id}/pdf"))->assertSuccessful();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('handover-checklist-'.$this->rentOut->property->number.'.pdf')
        ->and($response->getContent())->toBe('%PDF-1.7 fake');

    $notMine = checklistRentOut($this->world, facility: $this->colleague, leasing: null);
    checklistLines($notMine, [['Kitchen', 'Oven']]);
    $this->getJson(checklistUrl($this->world, "checklists/{$notMine->id}/pdf"))->assertNotFound();
});

it('renders the PDF and the sign page from one document, move-out photo included', function (): void {
    // A real file: both views only reference a photo that exists on the public disk.
    $photo = 'rent-out-checklist/test-doc-'.uniqid().'.png';
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    $absolute = public_path('storage/'.$photo);
    @mkdir(dirname($absolute), 0777, true);
    file_put_contents($absolute, $png);

    try {
        $this->fridge->update(['move_out_status' => 'not_ok', 'move_out_image_path' => $photo, 'move_out_comment' => 'Door seal torn']);
        RentOutChecklistSignature::create([
            'tenant_id' => $this->rentOut->tenant_id, 'rent_out_id' => $this->rentOut->id, 'phase' => 'move_in',
            'role' => 'facility_coordinator', 'signer_name' => 'Rahul', 'signature_path' => $photo, 'signed_at' => now(),
        ]);
        $this->me->givePermissionTo(Permission::findOrCreate('rent out checklist.edit', 'web'));
        $this->actingAs($this->me);

        $rentOut = RentOut::with(['account', 'group', 'building', 'property', 'type', 'checklistLines.item',
            'checklistSignatures', 'fixtureAreas.entries', 'facilityCoordinator', 'leasingCoordinator'])->find($this->rentOut->id);
        $pdf = view('print.rentout.checklist', ['rentOut' => $rentOut, 'companyLogo' => null, 'pages' => 1])->render();
        $screen = view('rentout-checklist.print', ['rentOut' => $rentOut])->render();

        foreach ([$pdf, $screen] as $html) {
            expect($html)
                ->toContain('UNIT HANDOVER & SNAGGING')
                ->toContain('Inventory &amp; Condition')
                ->toContain($this->fridge->item->name)
                ->toContain('Door seal torn')
                ->toContain('class="mo-img zoomable"')
                ->toContain('To be accomplished during Move-In');
        }

        // The PDF inlines images; the page links them.
        expect($pdf)->toContain('src="data:image/png;base64,')
            ->and($screen)->toContain('storage/'.$photo);

        // Unsigned boxes are pads on screen only; the signed one shows its image in both.
        expect($screen)->toContain('sigpad_move_in_lessee_'.$this->rentOut->id)
            ->not->toContain('sigpad_move_in_facility_coordinator_')
            ->and($pdf)->not->toContain('sigpad_');

        // A saved signature can be cleared on screen, never from the PDF.
        expect($screen)->toContain('Clear signature')
            ->and($pdf)->not->toContain('Clear signature');
    } finally {
        @unlink($absolute);
    }
});

it('saves an item photo on the web tab straight away, upright and scaled down', function (): void {
    $this->me->givePermissionTo(Permission::findOrCreate('rent out checklist.edit', 'web'));
    $this->actingAs($this->me);

    $tab = Livewire::test(ChecklistTab::class, ['rentOutId' => $this->rentOut->id])
        ->set('newImages.0', UploadedFile::fake()->image('fridge.png', 3000, 1500))
        ->assertHasNoErrors()
        ->assertDispatched('success');

    $path = $this->fridge->fresh()->image_path;
    expect($path)->toStartWith("rent-out-checklist/{$this->rentOut->id}/")->toEndWith('.jpg');
    Storage::disk('public')->assertExists($path);
    expect(getimagesizefromstring(Storage::disk('public')->get($path)))
        ->{0}->toBe(2000)
        ->{1}->toBe(1000);

    $tab->call('removeLineImage', 0);
    expect($this->fridge->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('turns a rejected web upload into an error toast', function (): void {
    $this->me->givePermissionTo(Permission::findOrCreate('rent out checklist.edit', 'web'));
    $this->actingAs($this->me);

    Livewire::test(ChecklistTab::class, ['rentOutId' => $this->rentOut->id])
        ->set('newImages.0', UploadedFile::fake()->create('lease.pdf', 100, 'application/pdf'))
        ->assertDispatched('error', fn ($name, $params) => str_contains($params[0]['message'], 'JPG, PNG, WEBP or HEIC'));

    expect($this->fridge->fresh()->image_path)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('accepts an iPhone HEIC photo and stores it as a JPEG', function (): void {
    if (! class_exists(Imagick::class) || ! Imagick::queryFormats('HEIC')) {
        $this->markTestSkipped('Imagick cannot write HEIC here.');
    }

    $heic = new Imagick();
    $heic->newImage(800, 600, 'skyblue');
    $heic->setImageFormat('heic');
    try {
        $blob = $heic->getImageBlob();
    } catch (ImagickException) {
        $this->markTestSkipped('No HEIC encoder installed.');
    }

    $this->me->givePermissionTo(Permission::findOrCreate('rent out checklist.edit', 'web'));
    $this->actingAs($this->me);

    Livewire::test(ChecklistTab::class, ['rentOutId' => $this->rentOut->id])
        ->set('newMoveOutImages.0', UploadedFile::fake()->createWithContent('IMG_6855.heic', $blob))
        ->assertDispatched('success');

    $path = $this->fridge->fresh()->move_out_image_path;
    expect($path)->toEndWith('.jpg')
        ->and(getimagesizefromstring(Storage::disk('public')->get($path))['mime'])->toBe('image/jpeg');
});

it('saves and removes a fixture photo on a saved entry straight away', function (): void {
    $this->me->givePermissionTo(Permission::findOrCreate('rent out checklist.edit', 'web'));
    $this->actingAs($this->me);
    $area = RentOutFixtureArea::create(['tenant_id' => $this->rentOut->tenant_id, 'rent_out_id' => $this->rentOut->id, 'category' => 'Kitchen', 'sort_order' => 1]);
    $entry = RentOutFixtureEntry::create(['tenant_id' => $this->rentOut->tenant_id, 'rent_out_fixture_area_id' => $area->id, 'status' => 'pending', 'sort_order' => 1]);

    $tab = Livewire::test(ChecklistTab::class, ['rentOutId' => $this->rentOut->id]);
    $a = collect($tab->get('fixtureAreas'))->search(fn ($area) => $area['category'] === 'Kitchen');

    $tab->set("newFixtureImages.{$a}.0.before", UploadedFile::fake()->image('before.jpg'))
        ->assertDispatched('success');

    $path = $entry->fresh()->before_image_path;
    expect($path)->toStartWith("rent-out-fixtures/{$this->rentOut->id}/");
    Storage::disk('public')->assertExists($path);

    $tab->call('removeFixtureImage', $a, 0, 'before');
    expect($entry->fresh()->before_image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('walks the web tab room by room, marking a whole room at once', function (): void {
    $this->me->givePermissionTo(Permission::findOrCreate('rent out checklist.edit', 'web'));
    $this->actingAs($this->me);

    $tab = Livewire::test(ChecklistTab::class, ['rentOutId' => $this->rentOut->id])
        ->assertSet('room', 'Kitchen')
        ->assertSet('phase', 'move_in')
        ->assertSeeHtml('wire:click="setMoveOut(')
        ->call('markRoom', 'Kitchen', 'move_in')
        ->call('setMoveOut', 1, 'not_ok')
        ->call('markRoom', 'Kitchen', 'move_out')
        ->call('stepQty', 0, 1)
        ->call('stepQty', 2, -5);

    expect(collect($tab->get('lines'))->map(fn ($l) => [$l['move_in_status'], $l['move_out_status'], $l['qty']])->all())->toBe([
        ['ok', 'ok', 2],
        ['ok', 'not_ok', 1],
        [null, null, 0],
    ]);

    $tab->call('setMoveOut', 1, 'not_ok');
    expect($tab->get('lines')[1]['move_out_status'])->toBeNull();

    // A hand-added area opens straight away; removing it falls back to the first room.
    $tab->set('room', 'Living Room')
        ->call('addFixtureArea', 'Balcony')
        ->assertSet('room', 'Balcony')
        ->call('removeFixtureArea', collect($tab->get('fixtureAreas'))->search(fn ($a) => $a['category'] === 'Balcony'))
        ->assertSet('room', 'Kitchen');
});

it('works a lease as one hand-over phase on the web tab', function (): void {
    $this->rentOut->update(['agreement_type' => 'lease']);
    $this->actingAs($this->me);

    Livewire::test(ChecklistTab::class, ['rentOutId' => $this->rentOut->id])
        ->set('phase', 'move_out')
        ->assertSet('phase', 'move_in')
        ->assertDontSeeHtml('wire:click="setMoveOut(')
        ->assertSee('Handover Terms');
});

it('keeps item names and rooms after the master item is deleted', function (): void {
    $this->actingAs($this->me);
    $this->fridge->item->delete();

    $tab = Livewire::test(ChecklistTab::class, ['rentOutId' => $this->rentOut->id]);

    expect($tab->get('lines')[0])
        ->name->toStartWith('Refrigerator')
        ->category->toBe('Kitchen');
    $tab->assertSee('Refrigerator');
});

it('clears a saved signature on the sign page so it can be signed again', function (): void {
    $this->me->givePermissionTo(Permission::findOrCreate('rent out checklist.edit', 'web'));
    $this->actingAs($this->me);
    $path = "rent-out-checklists/{$this->rentOut->id}/signatures/lessee.png";
    Storage::disk('public')->put($path, 'png');
    RentOutChecklistSignature::create([
        'tenant_id' => $this->rentOut->tenant_id, 'rent_out_id' => $this->rentOut->id, 'phase' => 'move_in',
        'role' => 'lessee', 'signer_name' => 'Leena', 'signature_path' => $path, 'signed_at' => now(),
    ]);

    Livewire::test(Sign::class, ['rentOut' => $this->rentOut, 'phase' => 'move_in', 'role' => 'lessee', 'signatureUrl' => '/storage/'.$path])
        ->assertSee('Clear signature')
        ->call('clear')
        ->assertRedirect(route('property::rent_out::checklist::print', $this->rentOut->id));

    expect(RentOutChecklistSignature::where('rent_out_id', $this->rentOut->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($path);
});

it('keeps the signatures of a sealed hand-over', function (): void {
    $this->me->givePermissionTo(Permission::findOrCreate('rent out checklist.edit', 'web'));
    $this->actingAs($this->me);
    signAll($this->rentOut, 'move_in');
    $this->rentOut->update(['actual_move_in_date' => now()->toDateString()]);

    Livewire::test(Sign::class, ['rentOut' => $this->rentOut, 'phase' => 'move_in', 'role' => 'lessee', 'signatureUrl' => '/storage/x.png', 'locked' => true])
        ->assertDontSee('Clear signature')
        ->call('clear')
        ->assertHasErrors('signature');

    expect(RentOutChecklistSignature::where('rent_out_id', $this->rentOut->id)->count())->toBe(3);
});
