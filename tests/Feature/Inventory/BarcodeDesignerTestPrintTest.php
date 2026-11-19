<?php

use App\Http\Controllers\BarcodeController;
use App\Support\BarcodeTemplateConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\Support\PosWorld;

/**
 * The designer's Print button prints the same proof the stage shows, sample
 * dates included; a real item's quick print has no dates to print.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(price: 50);
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    BarcodeTemplateConfiguration::saveConfiguration([
        'default_template' => 'shop',
        'templates' => [
            'shop' => ['name' => 'Shop', 'type' => 'standard', 'settings' => [
                'type' => 'standard',
                'mfg_date' => ['visible' => true],
                'exp_date' => ['visible' => true],
            ]],
        ],
    ]);
    request()->query->set('template', 'shop');

    // Grab the label HTML and stop before Chrome turns it into a PDF.
    $this->printedHtml = function (string $type = 'inventory', $id = null): string {
        $data = null;
        View::composer('inventory.label-types.standard.single', function ($view) use (&$data): void {
            if ($data === null) {
                $data = $view->getData();
                throw new RuntimeException('captured');
            }
        });

        try {
            app(BarcodeController::class)->print($type, $id);
        } catch (Throwable) {
        }

        expect($data)->not->toBeNull();

        return view('inventory.label-types.standard.single', $data)->render();
    };
});

it('prints the sample mfg and exp dates on the designer test print', function (): void {
    $html = ($this->printedHtml)();

    expect($html)
        ->toContain('Mfg Date: '.now()->format('d - m - Y'))
        ->toContain('Exp Date: '.now()->addDays(3)->format('d - m - Y'));
});

it('prints no dates on a real item quick print', function (): void {
    $inventoryId = DB::table('inventories')->where('product_id', $this->world->product->id)->value('id');

    $html = ($this->printedHtml)('inventory', $inventoryId);

    expect($html)->toContain('barcode-container')
        ->not->toContain('Mfg Date:')
        ->not->toContain('Exp Date:');
});
