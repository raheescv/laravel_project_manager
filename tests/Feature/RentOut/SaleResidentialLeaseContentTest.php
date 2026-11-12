<?php

use App\Actions\RentOut\GenerateResidentialLeaseAction;
use App\Enums\RentOut\AgreementType;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Property;
use App\Models\PropertyBuilding;
use App\Models\PropertyGroup;
use App\Models\PropertyType;
use App\Models\RentOut;
use Tests\Support\PosWorld;

/**
 * The off-plan sale agreement's Articles 1-17, Terms & Conditions and
 * Schedules 2-3 follow the "Skyline Contract Draft" wording.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    session(['branch_id' => $this->world->branch->id]);
    $this->actingAs($this->world->user);
});

function renderSaleLease(?Account $customer = null, ?PropertyGroup $group = null): string
{
    $property = new Property(['number' => '1002', 'floor' => '10', 'size' => '126.70']);
    $type = new PropertyType(['name' => '1 Bedroom', 'arabic_name' => 'غرفة وصالة']);
    $property->setRelation('type', $type);

    $rentOut = new RentOut(['agreement_type' => AgreementType::Lease, 'rent' => 1861000, 'start_date' => '2026-05-10', 'end_date' => '2029-12-31']);
    $rentOut->id = 1928;
    $rentOut->setRelation('property', $property);
    $rentOut->setRelation('type', $type);
    $rentOut->setRelation('building', new PropertyBuilding(['name' => 'MARINA TOWER', 'arabic_name' => 'برج مارينا']));
    $rentOut->setRelation('group', $group ?? new PropertyGroup(['name' => 'Bin Al Sheikh Marina Tower', 'arabic_name' => 'برج بن الشيخ مارينا']));
    $rentOut->setRelation('paymentTerms', collect());
    if ($customer) {
        $rentOut->setRelation('customer', $customer);
    }

    return view('print.booking.sale-residential-lease', [
        'rentOut' => $rentOut,
        'numberToWord' => ['english' => 'one million eight hundred sixty one thousand', 'arabic' => convert_number_to_arabic_words(1861000)],
    ])->render();
}

it('prints the contract date, the full seller block and the draft particulars on page one', function (): void {
    foreach ([
        'lessor_name_en' => 'BIN AL SHEIKH HOLDING COMPANY W.L.L',
        'lessor_capital_english' => '65.000.000 QAR',
        'lessor_capital_arabic' => '65.000.000 ريال قطري',
        'lessor_establishment_id' => '10-3804-00',
        'lessor_mobile' => '33670325',
    ] as $key => $value) {
        Configuration::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    expect(renderSaleLease())
        ->toContain('On <b>10 May 2026</b> this contract has been issued between both parties')
        ->toContain('أنه في يوم <b>10 مايو 2026</b> تحرر هذا العقد بين كل من الطرفين')
        ->toContain('<tr class="line"><td><span class="lbl">Capital:</span> <b>65.000.000 QAR</b></td><td lang="ar"><span class="lbl">رأس المال:</span> <b>65.000.000 ريال قطري</b></td></tr>')
        ->toContain(' · <span class="lbl">Est ID:</span> <b class="ltr-id">10-3804-00</b></td>')
        ->toContain('<span class="lbl">السجل التجاري:</span>')
        ->toContain(' · <span class="lbl">قيد المنشأة:</span> <b class="ltr-id">10-3804-00</b></td>')
        ->toContain('<span class="lbl">رقم هاتف:</span> <b>33670325</b>')
        ->toContain('{Hereinafter referred to as the first party or Seller}')
        ->toContain('{Hereinafter referred to as the second party – Purchaser}')
        ->toContain('registered under number (69060039) in the Real Estate Registry, with a total area of (25,120) square meters,')
        ->toContain('and covered by Building Permit Number (70701).')
        ->toContain('بترخيص بناء رقم (70701) الموصوف')
        ->toContain('Unit No.: MARINA TOWER – Unit (1002) – 10th Floor – 1 Bedroom Apartment.')
        ->toContain('<h3>Expected Completion Period</h3>')
        ->toContain('<b>Last quarter of 2029</b>')
        ->toContain('<h3>تاريخ الإنجاز المتوقع</h3>')
        ->toContain('<b>الربع الرابع من سنة 2029</b>');
});

it('prints the purchase price once in English words and once in Arabic words', function (): void {
    expect(renderSaleLease())
        ->toContain('<div lang="ar" style="text-align: center;">مليون وثمانمائة وواحد وستون ألف</div>')
        ->toContain('فقط (مليون وثمانمائة وواحد وستون ألف ريال قطري) لا غير');
});

it('leaves out optional seller lines that are not configured', function (): void {
    expect(renderSaleLease())
        ->not->toContain('<span class="lbl">Capital:</span>')
        ->not->toContain('Est ID:')
        ->not->toContain('<span class="lbl">رقم هاتف:</span>');
});

it('prints the same party details for the purchaser as for the seller', function (): void {
    $html = renderSaleLease(new Account([
        'name' => 'Doha Trading LLC',
        'po_box' => '4471',
        'cr_number' => '128844',
        'eid_number' => '10-3804-00',
        'authorized_by' => 'Hassan Ali',
        'id_no' => '25163400081',
        'nationality' => 'Qatar',
        'mobile' => '55512345',
        'email' => 'info@dohatrading.test',
    ]));

    expect($html)
        ->toContain('<span class="lbl">P.O. Box:</span> <b>4471</b>')
        ->toContain('<span class="lbl">CR No.:</span> <b>128844</b> · <span class="lbl">Est ID:</span> <b class="ltr-id">10-3804-00</b></td>')
        ->toContain('<span class="lbl">السجل التجاري:</span> <b>128844</b> · <span class="lbl">قيد المنشأة:</span> <b class="ltr-id">10-3804-00</b></td>')
        ->toContain('<span class="lbl">Authorized by:</span> <b>Hassan Ali</b>')
        ->toContain('<span class="lbl">ويمثلها:</span> <b>Hassan Ali</b>')
        ->toContain('<span class="lbl">QID No.:</span> <b>25163400081</b>')
        ->toContain('<span class="lbl">Nationality:</span> <b>Qatar</b>')
        ->toContain('<span class="lbl">Mobile:</span> <b>55512345</b>')
        ->toContain('<span class="lbl">Email:</span> <b>info@dohatrading.test</b>');
});

function salePurchaserBlock(Account $customer): string
{
    return str(renderSaleLease($customer))
        ->after('The Second Party (Purchaser)')
        ->before('Hereinafter referred to as the second party')
        ->toString();
}

it('leaves the CR number off an individual purchaser but keeps the rest', function (): void {
    $purchaserBlock = salePurchaserBlock(new Account([
        'name' => 'Fatima Noor',
        'entity_type' => 'individual',
        'cr_number' => '128844',
        'eid_number' => '10-3804-00',
        'authorized_by' => 'Hassan Ali',
    ]));

    expect($purchaserBlock)
        ->not->toContain('CR No.')
        ->not->toContain('السجل التجاري')
        ->not->toContain('128844')
        ->toContain('<td><span class="lbl">Est ID:</span> <b class="ltr-id">10-3804-00</b></td>')
        ->toContain('<span class="lbl">Authorized by:</span> <b>Hassan Ali</b>');
});

it('prints no registration row for an individual purchaser without an Est ID', function (): void {
    expect(salePurchaserBlock(new Account(['name' => 'Fatima Noor', 'entity_type' => 'individual'])))
        ->not->toContain('CR No.')
        ->not->toContain('Est ID')
        ->not->toContain('<tr class="line"><td></td>');
});

it('keeps the CR number for a company purchaser', function (): void {
    expect(salePurchaserBlock(new Account(['name' => 'Doha Trading LLC', 'entity_type' => 'company', 'cr_number' => '128844'])))
        ->toContain('<span class="lbl">CR No.:</span> <b>128844</b></td>');
});

it('keeps the CR and authorized by lines for a purchaser without those details', function (): void {
    $purchaserBlock = str(renderSaleLease(new Account(['name' => 'Fatima Noor', 'id_no' => '28800112233'])))
        ->after('The Second Party (Purchaser)')
        ->before('Hereinafter referred to as the second party')
        ->toString();

    expect($purchaserBlock)
        ->toContain('<span class="lbl">QID No.:</span> <b>28800112233</b>')
        ->toContain('<span class="lbl">CR No.:</span> <b></b></td>')
        ->toContain('<span class="lbl">Authorized by:</span> <b></b>')
        ->not->toContain('Est ID');
});

it('lists only the recorded instalments, without an invented monthly balance row', function (): void {
    expect(renderSaleLease())
        ->not->toContain('54 Months')
        ->not->toContain('المبلغ المتبقي');
});

it('prints the full draft wording of articles 1 to 17 with the record values', function (): void {
    $html = renderSaleLease();

    expect($html)
        ->toContain('for the project of Bin Al Sheikh Marina Tower in the Lusail area, on the land plot number (69060039)')
        ->toContain('approximately (126.70 sqm) square meters')
        ->toContain('only (<span style="text-transform: capitalize;">one million eight hundred sixty one thousand</span> Qatari Riyals)')
        ->toContain('during the first three years extendable (the manager)')
        ->toContain('defaulted on their payment obligation for a period of ninety (90) days')
        ->toContain('a compensation of (100.000 Qatari Riyal) One Hundred Thousand Qatari Riyal Only')
        ->toContain('Law No. 6 of the year 2014')
        ->toContain('البند السابع عشر')
        ->not->toContain('المادة الأولى')
        ->not->toContain('a compensation as agreed upon');
});

it('prints the new definitions and clauses in the terms and conditions', function (): void {
    expect(renderSaleLease())
        ->toContain('<strong>Earnest Money:</strong>')
        ->toContain('<strong>Real Estate Development Escrow Account:</strong>')
        ->toContain('jointly and severally responsible')
        ->toContain('<h3>Obligations of the First Party</h3>')
        ->toContain('<strong>50% of Contract Value to the First Party:</strong>')
        ->toContain('The Arabic version of the contract shall be considered the authoritative reference')
        ->toContain('<h3>Binding Effect of the Contract</h3>');
});

it('prints the draft unit plan heading and disclosure statement', function (): void {
    expect(renderSaleLease())
        ->toContain('10th Floor – MARINA TOWER – Unit 1002 – 1 Bedroom Apartment')
        ->toContain('Bin Al Sheikh Marina Tower - Lusail')
        ->toContain('living room set, master bedroom, dining room set, tv, secondary bedroom.')
        ->toContain('<tr><td class="k">التفاف</td><td>لتتناسب</td></tr>')
        ->not->toContain('تجارة بيلت');
});

it('prints in the ruled register layout with no grey fills for bond paper', function (): void {
    $html = renderSaleLease();

    expect($html)
        ->toContain('<div class="reg-title">')
        ->toContain('<td class="no"><span>17</span></td>')
        ->toContain('<td class="no"><span>41</span></td>')
        ->toContain('<div class="reg-initials">')
        ->not->toContain('#f1f3f5')
        ->not->toContain('fonts.googleapis.com');
});

it('repeats the initials strip on every page except the closing disclosure statement', function (): void {
    $html = renderSaleLease();
    $docWrapEnd = strpos($html, "        </tbody>\n    </table>");

    expect($docWrapEnd)->not->toBeFalse()
        ->and(substr_count($html, '<div class="reg-initials">'))->toBe(1)
        ->and(strpos($html, '<!-- Schedule 2 Component -->'))->toBeLessThan($docWrapEnd)
        ->and(strpos($html, '<!-- Schedule 3 Component -->'))->toBeLessThan($docWrapEnd)
        ->and(strpos($html, '<!-- Disclosure Statement Component'))->toBeGreaterThan($docWrapEnd)
        ->and(strrpos($html, 'Disclosure Statement'))->toBeGreaterThan($docWrapEnd);
});

it('reserves the letterhead and footer bands on bond paper', function (): void {
    Configuration::updateOrCreate(['key' => 'reservation_bond_paper_mode'], ['value' => 'yes']);
    Configuration::updateOrCreate(['key' => 'reservation_logo_height'], ['value' => '90']);
    Configuration::updateOrCreate(['key' => 'reservation_footer_height'], ['value' => '40']);

    expect(renderSaleLease())->toMatch('/margin: 90px 0 70px 0;/')
        ->and((new GenerateResidentialLeaseAction())->saleFooterHtml())
        ->toContain('padding-bottom: 40px;')
        ->toContain('<span class="pageNumber"></span> of <span class="totalPages"></span>');
});

it('embeds the Naskh Arabic font and prints party details as label: value lines', function (): void {
    $html = renderSaleLease();

    expect($html)
        ->toContain('font-family: "Print Naskh";')
        ->toContain('src: url("data:font/ttf;base64,')
        ->toContain('<tr class="line"><td><span class="lbl">P.O. Box:</span>')
        ->and(substr_count($html, 'data:font/ttf;base64,'))->toBe(2);
});

it('keeps each terms heading in the same row as its text so it never strands at a page foot', function (): void {
    $html = renderSaleLease();

    foreach (['Definitions', 'Obligations of the First Party', 'Sale and Assignment of the Contract'] as $heading) {
        expect($html)->not->toMatch('#<td><h3>'.preg_quote($heading, '#').'</h3></td>#');
    }

    expect($html)->toContain("<h3>Obligations of the First Party</h3>\n            <p>The first party must:</p>")
        ->not->toContain('border-top-style: hidden');
});

it('keeps a hyphenated ID inside the CR text whole and left-to-right', function (): void {
    Configuration::updateOrCreate(['key' => 'lessor_cr_no_arabic'], ['value' => '42463 رقم تعريف مؤسسة الشركة: 10-3804-00']);

    expect(renderSaleLease())
        ->toContain('42463 رقم تعريف مؤسسة الشركة: <span class="ltr-id">10-3804-00</span>');
});

it('frames the seller, purchaser, project details and disclosure statement as separate groups', function (): void {
    expect(substr_count(renderSaleLease(), '<table class="reg group">'))->toBe(4);
});

it('heads each group with an English and Arabic title band', function (): void {
    expect(renderSaleLease())
        ->toContain('<tr class="group-head">')
        ->toContain('<span>The First Party (Seller)</span><span lang="ar">الطرف الأول (البائع)</span>')
        ->toContain('<span>The Second Party (Purchaser)</span><span lang="ar">الطرف الثاني (المشتري)</span>')
        ->toContain('<span>Project Details</span><span lang="ar">تفاصيل المشروع</span>');
});

it('starts the disclosure statement on its own page as a separate group', function (): void {
    expect(renderSaleLease())
        ->toMatch('/<div class="page-break"><\/div>\s*<div class="container">\s*<!-- Disclosure Statement Component[^>]*-->\s*<table class="reg group">\s*<tr class="group-head">\s*<td colspan="2"><div><span>Disclosure Statement<\/span>/');
});

it('prints the property group location code in the contract meta', function (): void {
    $group = new PropertyGroup(['name' => 'Bin Al Sheikh Marina Tower', 'arabic_name' => 'برج بن الشيخ مارينا', 'location_code' => 'LSL-MT-01']);

    expect(renderSaleLease(group: $group))
        ->toMatch('/Location Code<\/td>\s*<td class="text-center bold">LSL-MT-01<\/td>/');
});

it('falls back to the property group name when no location code is set', function (): void {
    expect(renderSaleLease())
        ->toMatch('/Location Code<\/td>\s*<td class="text-center bold">Bin Al Sheikh Marina Tower<\/td>/');
});
