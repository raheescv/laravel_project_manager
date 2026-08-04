<?php

use App\Livewire\Settings\RentOutConfiguration;
use App\Support\RentOutChecklistNotes;
use App\Support\RichText;
use Livewire\Livewire;

it('renders the checklist notes editor', function () {
    Livewire::test(RentOutConfiguration::class)
        ->assertSee('Declaration')
        ->assertSeeHtml('rte-canvas');
});

it('sanitises a saved declaration', function () {
    $notes = RentOutChecklistNotes::normalize([
        'lease' => [
            'title' => 'To be accomplished during Handover',
            'declaration' => '<h3>WARRANTY</h3><p onclick="x()" dir="rtl">شروط <b>الضمان</b></p><script>alert(1)</script>',
        ],
    ]);

    expect($notes['lease']['declaration'])
        ->toContain('<h3>WARRANTY</h3>')
        ->toContain('dir="rtl"')
        ->not->toContain('onclick')
        ->not->toContain('script');

    expect(RichText::isBlank('<p><br></p>'))->toBeTrue();
    expect(RichText::toHtml("plain\nline"))->toBe("plain<br>\nline");
});

it('tidies what a paste from a word processor leaves behind', function () {
    $clean = RichText::sanitize(
        '<p><span style="font-size: 0.84rem">Handover and Acceptance</span></p>'
        .'<p><br></p><p><br></p>'
        .'<p>                    إقرار استلام الوحدة</p>'
    );

    // The editor's own computed font size must not travel into the print stylesheet,
    // spaces used as indentation collapse, stacked blank lines become one, and an
    // Arabic line gets its direction so it prints right-aligned.
    expect($clean)
        ->not->toContain('font-size')
        ->toContain('dir="rtl"')
        ->not->toContain('    ');

    expect(substr_count($clean, '<p><br></p>'))->toBe(1);
});
