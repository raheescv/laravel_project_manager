<?php

use App\Support\BarcodeTemplateConfiguration;

/**
 * Print alignment lives on each label template: how the label lands on the
 * stock when it prints straight to a TSC printer.
 */
it('starts a sticker template with a neutral print alignment', function (): void {
    expect(BarcodeTemplateConfiguration::normalizeSettings([], 'standard')['print'])
        ->toBe(['offset_x' => 0.0, 'offset_y' => 0.0, 'gap' => null, 'flip' => false]);
});

it('starts a jewellery tag template sized and aligned for the butterfly roll', function (): void {
    $settings = BarcodeTemplateConfiguration::normalizeSettings([], 'jewellery_tag');

    expect([$settings['width'], $settings['height'], $settings['wing_width'], $settings['neck_width']])->toEqual([60, 13, 20, 20])
        ->and($settings['print'])->toBe(['offset_x' => -0.3, 'offset_y' => 3.6, 'gap' => 1.5, 'flip' => true]);
});

it('keeps saved alignment within what the printer can take', function (): void {
    $print = BarcodeTemplateConfiguration::normalizeSettings([
        'print' => ['offset_x' => '1.26', 'offset_y' => -25, 'gap' => '', 'flip' => '1'],
    ], 'jewellery_tag')['print'];

    expect($print)->toBe(['offset_x' => 1.3, 'offset_y' => -20.0, 'gap' => null, 'flip' => true]);
});

it('keeps a gap typed in the designer', function (): void {
    $print = BarcodeTemplateConfiguration::normalizeSettings(['print' => ['gap' => 2.5]], 'standard')['print'];

    expect($print['gap'])->toBe(2.5);
});
