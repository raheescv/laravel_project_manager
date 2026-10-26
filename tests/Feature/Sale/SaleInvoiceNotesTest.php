<?php

use App\Actions\Sale\CreateAction;
use App\Helpers\SaleHelper;
use App\Livewire\Settings\SaleConfiguration;
use App\Models\Configuration;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → Sale → Receipt → "Sale Notes" decides whether the note the
 * cashier adds at checkout prints on the sale receipt. Off until switched on. The same flag reaches
 * the mobile app as `print.show_notes` on GET /settings/sale and can be
 * flipped from the app's Printer & Receipt screen.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    $inventoryId = DB::table('inventories')
        ->where('product_id', $this->world->product->id)
        ->where('branch_id', $this->world->branch->id)
        ->value('id');

    $response = (new CreateAction())->execute([
        'branch_id' => $this->world->branch->id,
        'account_id' => $this->world->accounts['general_customer'],
        'date' => today()->toDateString(),
        'sale_type' => 'normal',
        'status' => 'completed',
        'gross_amount' => 50,
        'item_discount' => 0,
        'tax_amount' => 0,
        'other_discount' => 0,
        'freight' => 0,
        'round_off' => 0,
        'paid' => 50,
        'notes' => 'Gift wrap, collect Friday',
        'items' => [[
            'inventory_id' => $inventoryId,
            'product_id' => $this->world->product->id,
            'unit_id' => $this->world->product->unit_id,
            'employee_id' => $this->world->user->id,
            'unit_price' => 50,
            'quantity' => 1,
            'conversion_factor' => 1,
            'discount' => 0,
            'tax' => 0,
        ]],
        'payments' => [[
            'payment_method_id' => $this->world->cashAccountId,
            'amount' => 50,
            'date' => today()->toDateString(),
        ]],
        'comboOffers' => [],
    ], $this->world->user->id);

    $this->saleId = $response['data']->id;
    Sale::whereKey($this->saleId)->update(['notes' => 'Gift wrap, collect Friday']);
});

it('prints the sale note with its arabic label once switched on', function (string $style): void {
    Configuration::updateOrCreate(['key' => 'thermal_printer_style'], ['value' => $style]);
    Configuration::updateOrCreate(['key' => 'enable_notes_in_print'], ['value' => 'yes']);

    $html = (new SaleHelper())->saleInvoice($this->saleId);

    expect($html)->toContain('Gift wrap, collect Friday');
    if ($style === 'with_arabic') {
        expect($html)->toContain('ملاحظات');
    } else {
        expect($html)->not->toContain('ملاحظات');
    }
})->with(['with_arabic', 'english_only']);

it('hides the sale note by default', function (): void {
    Configuration::where('key', 'enable_notes_in_print')->delete();

    expect((new SaleHelper())->saleInvoice($this->saleId))->not->toContain('Gift wrap, collect Friday');
});

it('prints no notes box for a sale without a note', function (): void {
    Configuration::updateOrCreate(['key' => 'enable_notes_in_print'], ['value' => 'yes']);
    Sale::whereKey($this->saleId)->update(['notes' => '   ']);

    expect((new SaleHelper())->saleInvoice($this->saleId))->not->toContain('notes-box"');
});

it('shows the switch off and saves it on from Settings → Sale', function (): void {
    $this->actingAs($this->world->user);

    Livewire::test(SaleConfiguration::class)
        ->assertSet('enable_notes_in_print', 'no')
        ->assertDontSeeHtml('id="sc-enable_notes_in_print" checked')
        ->set('enable_notes_in_print', 'yes')
        ->call('save');

    expect(Configuration::where('key', 'enable_notes_in_print')->value('value'))->toBe('yes');
});

it('sends the flag to the mobile app and lets the app change it', function (): void {
    $this->world->user->givePermissionTo(config('permission.models.permission')::firstOrCreate(['name' => 'configuration.settings', 'guard_name' => 'web']));
    Sanctum::actingAs($this->world->user);

    $this->getJson($this->world->url('/api/v1/settings/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.print.show_notes', false);

    $this->putJson($this->world->url('/api/v1/settings/sale/print'), ['show_notes' => true])
        ->assertSuccessful()
        ->assertJsonPath('data.print.show_notes', true);

    expect(Configuration::where('key', 'enable_notes_in_print')->value('value'))->toBe('yes');
});
