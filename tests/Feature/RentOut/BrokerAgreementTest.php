<?php

use App\Actions\RentOut\GenerateBrokerAgreementAction;
use App\Enums\RentOut\AgreementType;
use App\Livewire\Settings\RentOutConfiguration;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Models\RentOut;
use App\Support\RentOutPrintSettings;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Exclusive Leasing Brokerage Agreement printed from a sale (lease) agreement,
 * carried over from the accounts app.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

function brokerRentOut(array $attributes = [], ?Account $customer = null): RentOut
{
    $type = new PropertyType(['name' => '2 Bedroom', 'arabic_name' => 'غرفتين']);
    $property = new Property(['number' => '1504', 'floor' => '15', 'parking' => 'P2-14']);
    $property->setRelation('type', $type);

    $rentOut = new RentOut(array_merge(['agreement_type' => AgreementType::Lease, 'start_date' => '2026-12-01', 'free_month' => 0], $attributes));
    $rentOut->id = 3107;
    $rentOut->created_at = '2026-11-20 10:00:00';
    $rentOut->setRelation('property', $property);
    $rentOut->setRelation('type', $type);
    $rentOut->setRelation('building', new PropertyBuilding(['name' => 'MARINA TOWER', 'arabic_name' => 'برج مارينا', 'location' => 'Lusail']));
    $rentOut->setRelation('group', new PropertyGroup(['name' => 'Marina Residences', 'arabic_name' => 'مساكن مارينا']));
    $rentOut->setRelation('customer', $customer ?? new Account(['name' => 'Mariam Al Kuwari', 'id_no' => '28563400123', 'cr_number' => '55120', 'mobile' => '55001122', 'email' => 'mariam@example.com']));

    return $rentOut;
}

function brokerStoredRentOut(PosWorld $world, array $attributes = []): RentOut
{
    $tenantId = $world->tenant->id;
    $branchId = $world->branch->id;
    $group = PropertyGroup::create(['tenant_id' => $tenantId, 'name' => 'BA Group']);
    $building = PropertyBuilding::create(['tenant_id' => $tenantId, 'branch_id' => $branchId, 'property_group_id' => $group->id, 'name' => 'BA Building']);
    $type = PropertyType::create(['tenant_id' => $tenantId, 'name' => 'BA Type']);
    $property = Property::create([
        'tenant_id' => $tenantId, 'branch_id' => $branchId, 'property_group_id' => $group->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'number' => 'BA-1',
    ]);
    $customer = Account::create(['tenant_id' => $tenantId, 'name' => 'BA Owner', 'account_type' => 'asset']);

    return RentOut::create(array_merge([
        'tenant_id' => $tenantId, 'branch_id' => $branchId, 'account_id' => $customer->id, 'property_id' => $property->id,
        'property_building_id' => $building->id, 'property_type_id' => $type->id, 'property_group_id' => $group->id,
        'agreement_type' => 'lease', 'booking_type' => 'Long Term', 'start_date' => '2026-12-01', 'end_date' => '2027-11-30',
        'collection_payment_mode' => 'cash', 'created_by' => $world->user->id,
    ], $attributes));
}

/**
 * @return array<string, array<string, array{0: string, 1: ?string, 2: ?string, 3: string}>>
 */
function brokerSections(RentOut $rentOut): array
{
    return collect((new GenerateBrokerAgreementAction())->sections($rentOut))
        ->mapWithKeys(fn (array $section): array => [
            $section['english'] => collect($section['rows'])->keyBy(fn (array $row): string => $row[0])->all(),
        ])
        ->all();
}

it('prints the sale customer as the owner and the configured broker', function (): void {
    foreach ([
        'broker_name_english' => 'Gulf Brokerage',
        'broker_name_arabic' => 'الخليج للوساطة',
        'broker_cr_no' => '102542',
        'broker_license_no' => '977',
        'broker_authorized_by_english' => 'Salem M',
    ] as $key => $value) {
        Configuration::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    $sections = brokerSections(brokerRentOut());

    expect($sections['OWNER']['Name'][1])->toBe('Mariam Al Kuwari')
        ->and($sections['OWNER']['QID/CR'][1])->toBe('28563400123 / 55120')
        ->and($sections['OWNER']['Mobile'][1])->toBe('55001122')
        ->and($sections['BROKER']['Name'])->toBe(['Name', 'Gulf Brokerage', 'الخليج للوساطة', 'الاسم'])
        ->and($sections['BROKER']['Brokerage Licence No.'][1])->toBe('977')
        ->and($sections['BROKER']['Represented by'][2])->toBe('Salem M')
        ->and($sections['PROPERTY']['Property'][1])->toBe('Marina Residences, Lusail')
        ->and($sections['PROPERTY']['Tower'][2])->toBe('برج مارينا')
        ->and($sections['PROPERTY']['Type'][2])->toBe('غرفتين');
});

it('prints the broker agreement date, rent details and incentive typed on the agreement', function (): void {
    $sections = brokerSections(brokerRentOut([
        'broker_agreement_date' => '2026-11-25',
        'asking_rent' => 12500.50,
        'minimum_rent' => 11000,
        'available_from' => '2027-01-01',
        'launch_incentive_en' => 'First month free',
        'launch_incentive_ar' => 'الشهر الأول مجاناً',
    ]));

    expect($sections['AGREEMENT']['Date'][1])->toBe(systemDate('2026-11-25'))
        ->and($sections['RENT']['Approved asking rent'][1])->toBe('QAR 12,500.5 / month')
        ->and($sections['RENT']['Minimum rent'][2])->toBe('11,000 ر.ق')
        ->and($sections['RENT']['Available from'][1])->toBe(systemDate('2027-01-01'))
        ->and($sections['RENT']['Launch incentive (if approved)'])->toBe(['Launch incentive (if approved)', 'First month free', 'الشهر الأول مجاناً', 'العرض الترويجي المعتمد (إن وجد)']);
});

it('falls back to the created date, start date and free months, never the sale price', function (): void {
    $sections = brokerSections(brokerRentOut(['rent' => 1861000, 'free_month' => 2]));

    expect($sections['AGREEMENT']['Date'][1])->toBe(systemDate('2026-11-20'))
        ->and($sections['RENT']['Approved asking rent'][1])->toBe('')
        ->and($sections['RENT']['Available from'][1])->toBe(systemDate('2026-12-01'))
        ->and($sections['RENT']['Launch incentive (if approved)'][1])->toBe('2 months rent free')
        ->and($sections['RENT']['Launch incentive (if approved)'][2])->toBe('2 أشهر مجاناً');
});

it('prints the configured clauses, the owner on the signature line and a blank line for missing values', function (): void {
    RentOutPrintSettings::saveBrokerClauses([
        ['heading_en' => 'Appointment and scope', 'heading_ar' => 'التعيين ونطاق العمل', 'text_en' => 'The Owner appoints the broker.', 'text_ar' => 'يعين المالك الوسيط.'],
    ]);

    $html = (new GenerateBrokerAgreementAction())->html(brokerRentOut(customer: new Account(['name' => 'Mariam Al Kuwari'])));

    expect($html)
        ->toContain('EXCLUSIVE LEASING BROKERAGE AGREEMENT')
        ->toContain('<b>1. Appointment and scope</b>')
        ->toContain('<b>1. التعيين ونطاق العمل</b>')
        ->toContain('The Owner appoints the broker.')
        ->toContain('Name / الاسم: <b>Mariam Al Kuwari</b>')
        ->toContain('<b>______________</b>');
});

it('hides the logo and reserves the letterhead space in broker bond paper mode', function (): void {
    Configuration::updateOrCreate(['key' => 'broker_bond_paper_mode'], ['value' => 'yes']);
    Configuration::updateOrCreate(['key' => 'broker_logo_height'], ['value' => '100']);
    Configuration::updateOrCreate(['key' => 'broker_footer_height'], ['value' => '60']);

    $html = (new GenerateBrokerAgreementAction())->html(brokerRentOut());

    expect($html)
        ->toContain('height: 125px;')
        ->toContain('margin-top: -25px;')
        ->toContain('height: 60px;')
        ->not->toContain('alt="Logo"');
});

it('saves the broker details, clauses and bond paper layout from settings', function (): void {
    Livewire::test(RentOutConfiguration::class)
        ->assertSee('Broker Agreement')
        ->set('print_settings.broker_name_english', 'Gulf Brokerage')
        ->set('print_settings.broker_license_no', '977')
        ->call('addBrokerClause')
        ->set('broker_clauses.0.heading_en', 'Term')
        ->set('broker_clauses.0.text_en', 'Valid for 90 days.')
        ->call('addBrokerClause')
        ->call('addBrokerClause')
        ->set('broker_clauses.2.heading_en', 'Fees')
        ->call('moveBrokerClause', 2, -1)
        ->set('broker_bond_paper_mode', 'yes')
        ->set('broker_logo_height', 90)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('broker_clauses', [
            ['heading_en' => 'Term', 'heading_ar' => '', 'text_en' => 'Valid for 90 days.', 'text_ar' => ''],
            ['heading_en' => 'Fees', 'heading_ar' => '', 'text_en' => '', 'text_ar' => ''],
        ]);

    expect(RentOutPrintSettings::broker('broker_name_english'))->toBe('Gulf Brokerage')
        ->and(RentOutPrintSettings::broker('broker_license_no'))->toBe('977')
        ->and(collect(RentOutPrintSettings::brokerClauses())->pluck('heading_en')->all())->toBe(['Term', 'Fees'])
        ->and(Configuration::where('key', 'broker_bond_paper_mode')->value('value'))->toBe('yes')
        ->and(Configuration::where('key', 'broker_logo_height')->value('value'))->toBe('90');
});

it('stores the broker fields on the agreement and clears blank inputs to null', function (): void {
    $rentOut = brokerStoredRentOut($this->world, [
        'broker_agreement_date' => '2026-11-25', 'asking_rent' => 9000, 'available_from' => '2027-01-01', 'launch_incentive_en' => 'One month free',
    ]);

    $this->world->user->givePermissionTo(Permission::findOrCreate('rent out lease.edit', 'web'));

    Livewire::test(\App\Livewire\RentOut\Page::class, ['type' => 'RentOut', 'table_id' => $rentOut->id, 'agreementType' => 'lease'])
        ->assertSet('rent_outs.broker_agreement_date', '2026-11-25')
        ->assertSet('rent_outs.available_from', '2027-01-01')
        ->set('rent_outs.asking_rent', '')
        ->set('rent_outs.minimum_rent', '8500')
        ->call('save')
        ->assertNotDispatched('error');

    $rentOut->refresh();
    expect($rentOut->asking_rent)->toBeNull()
        ->and((float) $rentOut->minimum_rent)->toBe(8500.0)
        ->and($rentOut->broker_agreement_date->toDateString())->toBe('2026-11-25')
        ->and($rentOut->launch_incentive_en)->toBe('One month free');
});

it('offers the broker agreement on sale agreements only', function (): void {
    $this->world->user->givePermissionTo(Permission::findOrCreate('rent out lease.view', 'web'));
    $rental = brokerStoredRentOut($this->world, ['agreement_type' => 'rental']);

    $this->get(route('print::rentout::broker-agreement', $rental->id))->assertNotFound();
});

it('requires the sale agreement view permission to print the broker agreement', function (): void {
    $this->get(route('print::rentout::broker-agreement', 1))->assertForbidden();
});
