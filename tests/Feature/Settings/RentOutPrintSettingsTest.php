<?php

use App\Enums\RentOut\AgreementType;
use App\Livewire\Settings\RentOutConfiguration;
use App\Models\Configuration;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\RentOut;
use App\Models\RentOutTransaction;
use App\Support\RentOutPrintSettings;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → Rent Out → Agreement Text / Lessor Details / Agreement Colors,
 * carried over from the accounts app's General Configuration, and the
 * reservation form and tenancy agreement PDFs that print them.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

function renderRentalLease(array $overrides = []): string
{
    $rentOut = new RentOut(['agreement_type' => AgreementType::Rental, 'start_date' => '2026-03-01']);
    $rentOut->id = 2201;

    return view('print.booking.rental-residential-lease', array_merge([
        'rentOut' => $rentOut,
        'lessorData' => [],
        'lesseeData' => [],
        'premisesDetails' => [],
        'contractDetails' => [],
        'title' => RentOutPrintSettings::text('tenancy_agreement_title_english'),
        'titleArabic' => RentOutPrintSettings::text('tenancy_agreement_title_arabic'),
        'projectNameEnglish' => RentOutPrintSettings::text('tenancy_project_name_english'),
        'projectNameArabic' => RentOutPrintSettings::text('tenancy_project_name_arabic'),
        'type' => 'normal',
    ], $overrides))->render();
}

function renderReservation(): string
{
    $rentOut = new RentOut(['agreement_type' => AgreementType::Rental]);
    $rentOut->id = 2202;

    return view('print.booking.reservation-form', [
        'rentOut' => $rentOut,
        'propertyDetails' => [],
        'buyerDetails' => [],
        'agentDetails' => [],
    ])->render();
}

it('saves agreement wording, lessor details and colours from the settings tabs', function (): void {
    Livewire::test(RentOutConfiguration::class)
        ->assertSeeInOrder(['Agreement Text', 'Lessor Details', 'Agreement Colors'])
        ->set('print_settings.company_name_english', 'Bin Al Sheikh Real Estate Brokerage')
        ->set('print_settings.tenancy_agreement_title_english', 'TENANCY AGREEMENT')
        ->set('print_settings.lessor_po_box_arabic', '13321 الدوحة')
        ->call('applyColorPreset', 2)
        ->call('save')
        ->assertHasNoErrors();

    expect(Configuration::where('key', 'company_name_english')->value('value'))->toBe('Bin Al Sheikh Real Estate Brokerage')
        ->and(Configuration::where('key', 'lessor_po_box_arabic')->value('value'))->toBe('13321 الدوحة')
        ->and(RentOutPrintSettings::colors())->toBe(['primary' => '#0f766e', 'secondary' => '#d5efec', 'primaryInk' => '#ffffff']);
});

it('rejects a colour that is not a #RRGGBB hex value', function (): void {
    Livewire::test(RentOutConfiguration::class)
        ->set('print_settings.agreement_primary_color', 'blue')
        ->call('save')
        ->assertHasErrors(['print_settings.agreement_primary_color' => 'regex']);
});

it('keeps printing the built-in wording and blue while nothing is configured', function (): void {
    $html = renderRentalLease();

    expect($html)->toContain('RESIDENTIAL LEASE')
        ->toContain('background: #1b7bbc')
        ->toContain('background: #d9f0fb')
        ->toContain('This Contract is made on <b>'.systemDate('2026-03-01').'</b>')
        ->toContain('تفاصيل العقار');

    expect(renderReservation())->toContain('Reservation Form For An Apartment')->toContain('<b>Management</b>');
});

it('prints the configured titles, clauses, company name and colours', function (): void {
    foreach ([
        'tenancy_agreement_title_english' => 'TENANCY AGREEMENT',
        'tenancy_agreement_title_arabic' => 'عقد إيجار',
        'tenancy_project_name_english' => 'Bin Al Sheikh Towers - Doha',
        'tenancy_contract_made_on_english' => 'Signed on {date} between <both> parties.',
        'tenancy_contract_terms_english' => 'See the attached pages.',
        'reservation_form_title_english' => 'Reservation Form For An Office',
        'company_name_english' => 'Bin Al Sheikh Real Estate Brokerage',
        'agreement_primary_color' => '#f5ecd2',
        'agreement_secondary_color' => '#e5e7eb',
    ] as $key => $value) {
        Configuration::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    $html = renderRentalLease();

    expect($html)->toContain('TENANCY AGREEMENT')
        ->toContain('عقد إيجار')
        ->toContain('Bin Al Sheikh Towers - Doha')
        ->toContain('Signed on <b>'.systemDate('2026-03-01').'</b> between &lt;both&gt; parties.')
        ->toContain('See the attached pages.')
        ->toContain('background: #f5ecd2')
        ->toContain('color: #333333')
        ->toContain('background: #e5e7eb')
        ->not->toContain('#1b7bbc');

    expect(renderReservation())->toContain('Reservation Form For An Office')
        ->toContain('Bin Al Sheikh Real Estate Brokerage')
        ->toContain('background: #f5ecd2');
});

it('reads the lessor English and Arabic values, falling back to the old single keys', function (): void {
    Configuration::updateOrCreate(['key' => 'lessor_po_box'], ['value' => '9999']);
    Configuration::updateOrCreate(['key' => 'lessor_cr_no_arabic'], ['value' => '42463 عربي']);

    expect(RentOutPrintSettings::lessor('lessor_po_box_english'))->toBe('9999')
        ->and(RentOutPrintSettings::lessor('lessor_cr_no_arabic'))->toBe('42463 عربي')
        ->and(RentOutPrintSettings::lessor('lessor_cr_no_english'))->toBe('');
});

it('falls back to the company profile name for the agency name', function (): void {
    Configuration::updateOrCreate(['key' => 'company_name'], ['value' => 'BAS']);

    expect(RentOutPrintSettings::companyName())->toBe('BAS')
        ->and(RentOutPrintSettings::companyName('arabic'))->toBe('');

    Configuration::updateOrCreate(['key' => 'company_name_english'], ['value' => 'Bin Al Sheikh']);

    expect(RentOutPrintSettings::companyName())->toBe('Bin Al Sheikh');
});

it('flags the Agreement Text tab when a clause fails validation', function (): void {
    Livewire::test(RentOutConfiguration::class)
        ->set('print_settings.tenancy_contract_terms_arabic', str_repeat('ب', 2001))
        ->call('save')
        ->assertHasErrors(['print_settings.tenancy_contract_terms_arabic' => 'max'])
        ->assertSee('Needs attention');
});

it('prints the configured seller details verbatim on the sale agreement', function (): void {
    Configuration::updateOrCreate(['key' => 'lessor_po_box_english'], ['value' => '13321, Doha , State of Qatar']);
    Configuration::updateOrCreate(['key' => 'lessor_po_box_arabic'], ['value' => '13321  الدوحة  دولة قطر']);
    Configuration::updateOrCreate(['key' => 'lessor_authorized_by_arabic'], ['value' => 'السيد حسن محسن']);

    $property = new Property(['number' => '709']);
    $property->setRelation('type', new PropertyType(['name' => 'Apartment']));
    $rentOut = new RentOut(['agreement_type' => AgreementType::Lease, 'rent' => 1000, 'start_date' => '2026-03-01', 'end_date' => '2027-03-01']);
    $rentOut->id = 2203;
    $rentOut->setRelation('property', $property);
    $rentOut->setRelation('paymentTerms', collect());

    $html = view('print.booking.sale-residential-lease', [
        'rentOut' => $rentOut,
        'numberToWord' => ['english' => 'one thousand', 'arabic' => 'one thousand'],
    ])->render();

    expect($html)->toContain('<span class="underline">13321, Doha , State of Qatar</span> &nbsp;|')
        ->toContain('<span class="underline">13321  الدوحة  دولة قطر</span> &nbsp;|')
        ->toContain('يمثلها: السيد حسن محسن')
        ->not->toContain('State of Qatar</span>, Doha, Qatar')
        ->not->toContain('السيد/ السيد');
});

it('prints the company website on the rent-out receipt and voucher headers', function (string $view): void {
    $rentOut = new RentOut(['agreement_no' => 'AG-1']);
    $rentOut->setRelation('customer', null);
    $rentOut->setRelation('property', null);
    $rentOut->setRelation('building', null);
    $payment = new RentOutTransaction(['credit' => 1000, 'debit' => 0]);
    $payment->id = 76329;
    $payment->setRelation('account', null);

    $html = view($view, [
        'payment' => $payment,
        'rentOut' => $rentOut,
        'companyName' => 'Bin Al Sheikh Holding',
        'companyPhone' => '+974 4001 1911',
        'companyAddress' => 'Suhaim Bin Hamad Street, Doha, Qatar',
        'companyEmail' => 'Reception@binalsheikh.com',
        'companyWebsite' => 'www.binalsheikh.com',
        'companyLogo' => null,
    ])->render();

    expect($html)->toContain('Reception@binalsheikh.com')->toContain('<br>www.binalsheikh.com');
})->with(['print.rentout.receipt', 'print.rentout.voucher']);
