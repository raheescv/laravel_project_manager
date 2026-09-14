<?php

use App\Http\Controllers\BarcodeController;
use App\Support\BarcodeTemplateConfiguration;
use Illuminate\Http\Request;
use Tests\Support\PosWorld;

/**
 * The template designer saves its whole settings block. A page left open
 * elsewhere must not write its older copy over newer settings.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);

    BarcodeTemplateConfiguration::saveConfiguration([
        'default_template' => 'tag',
        'templates' => [
            'tag' => ['name' => 'Tag', 'type' => 'jewellery_tag', 'settings' => BarcodeTemplateConfiguration::defaultSettings('jewellery_tag')],
        ],
    ]);
});

function saveBarcodeTemplate(array $payload)
{
    return app(BarcodeController::class)->saveConfigurationTemplate(Request::create('/inventory/barcode/configuration/tag/save', 'POST', $payload), 'tag');
}

function currentBarcodeTemplateVersion(): string
{
    return BarcodeTemplateConfiguration::templateVersion(BarcodeTemplateConfiguration::getConfiguration()['templates']['tag']);
}

it('hands the designer the version of the template it loads', function (): void {
    expect(app(BarcodeController::class)->configurationData('tag')->getData(true)['version'])->toBe(currentBarcodeTemplateVersion());
});

it('saves over the version the page loaded and returns the next one', function (): void {
    $version = currentBarcodeTemplateVersion();
    $settings = BarcodeTemplateConfiguration::defaultSettings('jewellery_tag');
    $settings['print']['offset_y'] = 2.5;

    $response = saveBarcodeTemplate(['templateName' => 'Tag', 'settings' => $settings, 'version' => $version]);

    expect($response->getStatusCode())->toBe(200)
        ->and(BarcodeTemplateConfiguration::resolveSettings('tag')['settings']['print']['offset_y'])->toBe(2.5)
        ->and($response->getData(true)['version'])->toBe(currentBarcodeTemplateVersion())
        ->and($response->getData(true)['version'])->not->toBe($version);
});

it('refuses an older copy and sends back the current template', function (): void {
    $settings = BarcodeTemplateConfiguration::defaultSettings('jewellery_tag');
    $settings['print']['offset_y'] = -9;

    $stale = saveBarcodeTemplate(['templateName' => 'Tag', 'settings' => $settings, 'version' => 'from-an-old-tab']);
    $unversioned = saveBarcodeTemplate(['templateName' => 'Tag', 'settings' => $settings]);

    expect($stale->getStatusCode())->toBe(409)
        ->and($unversioned->getStatusCode())->toBe(409)
        ->and($stale->getData(true)['settings']['print']['offset_y'])->toBe(3.6)
        ->and(BarcodeTemplateConfiguration::resolveSettings('tag')['settings']['print']['offset_y'])->toBe(3.6);
});
