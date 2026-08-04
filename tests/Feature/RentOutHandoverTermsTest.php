<?php

use App\Enums\RentOut\AgreementType;
use App\Models\RentOut;
use App\Support\RentOutHandoverTerms;

it('prints nothing for a booking that has no terms', function () {
    // An untouched booking prints the checklist exactly as it printed before
    // handover terms existed.
    $lease = new RentOut(['agreement_type' => AgreementType::Lease]);

    expect(RentOutHandoverTerms::of($lease)['clauses'])->toBe([]);
    expect(RentOutHandoverTerms::forPrint($lease))->toBe([]);
    expect(RentOutHandoverTerms::forPrint(null))->toBe([]);
});

it('sanitises a clause and drops the empty ones', function () {
    $terms = RentOutHandoverTerms::normalize([
        'heading_en' => '<b>Warranty Terms</b>',
        'heading_ar' => 'شروط الضمان',
        'clauses' => [
            [
                'title_en' => 'Electrical Appliances &amp; Electronics',
                'title_ar' => 'الأجهزة الكهربائية',
                'body_en' => '<p onclick="x()">One (1) year warranty.</p><script>alert(1)</script>',
                'body_ar' => '<p>ضمان لمدة سنة واحدة.</p>',
            ],
            ['title_en' => '', 'title_ar' => '', 'body_en' => '<p><br></p>', 'body_ar' => ''],
        ],
    ]);

    expect($terms['clauses'])->toHaveCount(1);
    // Headings are plain text — markup is stripped and entities decoded.
    expect($terms['heading_en'])->toBe('Warranty Terms');
    expect($terms['clauses'][0]['title_en'])->toBe('Electrical Appliances & Electronics');
    expect($terms['clauses'][0]['body_en'])
        ->toContain('One (1) year warranty.')
        ->not->toContain('onclick')
        ->not->toContain('script');
    // An Arabic line without a direction gets one, so it prints right-aligned.
    expect($terms['clauses'][0]['body_ar'])->toContain('dir="rtl"');
});

it('numbers the clauses on both sides and never prints on a rental', function () {
    $stored = RentOutHandoverTerms::normalize([
        'heading_en' => 'Warranty Terms',
        'heading_ar' => 'شروط الضمان',
        'clauses' => [
            ['title_en' => 'Furniture', 'title_ar' => 'الأثاث', 'body_en' => '<p>No warranty.</p>', 'body_ar' => '<p dir="rtl">دون أي ضمان.</p>'],
            ['title_en' => 'General Exclusions', 'title_ar' => 'الاستثناءات', 'body_en' => '<ul><li>Misuse.</li></ul>', 'body_ar' => ''],
        ],
    ]);

    $lease = new RentOut(['agreement_type' => AgreementType::Lease, 'handover_terms' => $stored]);
    $printed = RentOutHandoverTerms::forPrint($lease);

    expect($printed['clauses'])->toHaveCount(2);
    expect($printed['has_arabic'])->toBeTrue();
    // Numbering comes from position, so inserting a clause never means renumbering.
    expect($printed['clauses'][0]['no_en'])->toBe('1.');
    expect($printed['clauses'][1]['no_en'])->toBe('2.');
    expect($printed['clauses'][1]['no_ar'])->toBe('٢.');
    expect($printed['clauses'][1]['body_en'])->toContain('<li>Misuse.</li>');

    // A rental hands the unit back rather than handing it over — its checklist
    // carries the Move-Out block instead, and never the handover terms.
    $rental = new RentOut(['agreement_type' => AgreementType::Rental, 'handover_terms' => $stored]);
    expect(RentOutHandoverTerms::forPrint($rental))->toBe([]);
});

it('gives the full width to English when the booking has no Arabic', function () {
    $lease = new RentOut([
        'agreement_type' => AgreementType::Lease,
        'handover_terms' => RentOutHandoverTerms::normalize([
            'clauses' => [['title_en' => 'Furniture', 'body_en' => '<p>No warranty.</p>']],
        ]),
    ]);

    $printed = RentOutHandoverTerms::forPrint($lease);

    expect($printed['has_arabic'])->toBeFalse();
    // An empty heading falls back to a structural label, not to wording.
    expect($printed['heading_en'])->toBe(RentOutHandoverTerms::FALLBACK_HEADING);
});

it('offers a loadable sample that survives sanitising', function () {
    $sample = RentOutHandoverTerms::sample();

    expect($sample['clauses'])->toHaveCount(4);
    expect($sample['clauses'][1]['title_en'])->toBe('Electrical Appliances & Electronics');
    expect($sample['clauses'][3]['body_en'])->toContain('<li>Normal wear and tear.</li>');
    expect($sample['clauses'][3]['body_ar'])->toContain('dir="rtl"');
});
