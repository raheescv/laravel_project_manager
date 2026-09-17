<?php

use App\Models\Configuration;
use App\Models\EmailTemplate as EmailTemplateModel;
use App\Services\NavigationService;
use App\Support\ModuleAccess;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;
use Tests\Support\StudentWorld;

/**
 * Student cards, the parent portal and QPay top-ups belong to the School module.
 * A tenant running any other system — or none chosen yet — never sees them.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id, 'name' => 'student.view', 'guard_name' => 'web',
    ]));
});

function setSystem(PosWorld $world, ?string $system): void
{
    $system === null
        ? Configuration::where('tenant_id', $world->tenant->id)->where('key', 'active_module')->delete()
        : Configuration::updateOrCreate(['tenant_id' => $world->tenant->id, 'key' => 'active_module'], ['value' => $system]);
}

it('is on only for the School Module system', function (?string $system, bool $expected): void {
    setSystem($this->world, $system);

    expect(ModuleAccess::school())->toBe($expected);
})->with([
    'no system chosen' => [null, false],
    'POS Module' => ['POS Module', false],
    'Tailor Module' => ['Tailor Module', false],
    'School Module' => ['School Module', true],
]);

it('keeps the base modules on when no system is chosen', function (): void {
    setSystem($this->world, null);

    expect(ModuleAccess::enabled('sales'))->toBeTrue();
});

it('hides every student route, the parent portal and the card API outside the School module', function (): void {
    setSystem($this->world, 'POS Module');

    $this->actingAs($this->world->user)->get($this->world->url('/student'))->assertNotFound();
    $this->getJson($this->world->url('/api/v1/parent/school'))->assertNotFound();
    $this->postJson($this->world->url('/api/v1/parent/login'), ['login' => '55123456', 'password' => 'secret-pass'])->assertNotFound();
    $this->call('POST', $this->world->url('/api/v1/parent/qpay/return'))->assertNotFound();

    Sanctum::actingAs($this->world->user);
    $this->getJson($this->world->url('/api/v1/students/card/04A21B9C'))->assertNotFound();
    $this->getJson($this->world->url('/api/v1/settings/sale'))->assertOk()->assertJsonPath('data.school_enabled', false);
});

it('opens them for a School tenant', function (): void {
    StudentWorld::enableSchool($this->world);

    $this->actingAs($this->world->user)->get($this->world->url('/student'))->assertOk();
    $this->getJson($this->world->url('/api/v1/parent/school'))->assertOk();

    Sanctum::actingAs($this->world->user);
    $this->getJson($this->world->url('/api/v1/settings/sale'))->assertOk()->assertJsonPath('data.school_enabled', true);
});

it('refuses a card sale from the till when the tenant is not a school', function (): void {
    StudentWorld::enableSchool($this->world);
    $student = StudentWorld::enrol($this->world);
    StudentWorld::topUp($this->world, $student, 100);
    setSystem($this->world, 'POS Module');

    Sanctum::actingAs($this->world->user);
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'customerName' => $student->name,
        'studentAccountId' => $student->id,
        'cardUid' => '04A21B9C',
        'paymentMethod' => 'student_card',
    ]))->assertUnprocessable()->assertJsonFragment(['success' => false]);
});

it('keeps Students out of the sidebar and the parent email template out of Settings', function (): void {
    foreach ([null, 'POS Module'] as $system) {
        setSystem($this->world, $system);

        expect(collect(NavigationService::getNavigationItems())->pluck('id'))->not->toContain('students')
            ->and(EmailTemplateModel::modules())->not->toHaveKey('student_portal');
    }

    StudentWorld::enableSchool($this->world);
    expect(collect(NavigationService::getNavigationItems())->pluck('id'))->toContain('students')
        ->and(EmailTemplateModel::modules())->toHaveKey('student_portal');
});
