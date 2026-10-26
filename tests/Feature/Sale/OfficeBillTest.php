<?php

use App\Actions\Sale\CreateAction;
use App\Helpers\SaleHelper;
use App\Http\Resources\V1\Auth\AuthUserResource;
use App\Http\Resources\V1\Sale\SaleResource;
use App\Livewire\Sale\View;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → Sale → Thermal Printer Style → "Office Bill": a compact English-only
 * receipt carrying the biller's own Bill No — `{users.code}/{n}`, the nth
 * sale that biller rang up on the sale (day session) date.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->update(['code' => '9', 'name' => 'Haneefa Babu']);

    $this->ringUp = function (?int $userId = null, string $status = 'completed', ?string $date = null): Sale {
        $date ??= today()->toDateString();
        $inventoryId = DB::table('inventories')
            ->where('product_id', $this->world->product->id)
            ->where('branch_id', $this->world->branch->id)
            ->value('id');

        $response = (new CreateAction())->execute([
            'branch_id' => $this->world->branch->id,
            'account_id' => $this->world->accounts['general_customer'],
            'date' => $date,
            'sale_type' => 'normal',
            'status' => $status,
            'gross_amount' => 50,
            'item_discount' => 0,
            'tax_amount' => 0,
            'other_discount' => 0,
            'freight' => 0,
            'round_off' => 0,
            'paid' => 50,
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
                'date' => $date,
            ]],
            'comboOffers' => [],
        ], $userId ?? $this->world->user->id);

        expect($response['success'])->toBeTrue($response['message'] ?? '');

        return $response['data']->fresh();
    };
});

it('numbers each biller\'s sales from 1 as code/n', function (): void {
    $first = ($this->ringUp)();
    $second = ($this->ringUp)();

    expect($first->bill_no)->toBe('9/1')
        ->and($second->bill_no)->toBe('9/2')
        ->and($second->invoice_no)->not->toBe($second->bill_no);
});

it('counts every biller separately', function (): void {
    $other = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
        'is_admin' => 1,
        'code' => '14',
    ]);

    ($this->ringUp)();
    $otherSale = ($this->ringUp)($other->id);
    $ownSecond = ($this->ringUp)();

    expect($otherSale->bill_no)->toBe('14/1')
        ->and($ownSecond->bill_no)->toBe('9/2');
});

it('restarts the count on a new sale date', function (): void {
    ($this->ringUp)(date: today()->subDay()->toDateString());
    ($this->ringUp)(date: today()->subDay()->toDateString());

    expect(($this->ringUp)()->bill_no)->toBe('9/1');
});

it('keeps the number when the sale is edited and does not reuse a deleted one', function (): void {
    $first = ($this->ringUp)();
    $second = ($this->ringUp)();

    $first->update(['notes' => 'edited']);
    $second->delete();

    expect($first->fresh()->bill_no)->toBe('9/1')
        ->and(($this->ringUp)()->bill_no)->toBe('9/3');
});

it('numbers a draft only once it is completed', function (): void {
    $draft = ($this->ringUp)(status: 'draft');
    expect($draft->bill_no)->toBeNull();

    ($this->ringUp)();
    $draft->update(['status' => 'completed']);

    expect($draft->fresh()->bill_no)->toBe('9/2');
});

it('falls back to the user id when the biller has no code', function (): void {
    $this->world->user->update(['code' => null]);

    expect(($this->ringUp)()->bill_no)->toBe($this->world->user->id.'/1');
});

it('prints only the office bill block', function (): void {
    Configuration::updateOrCreate(['key' => 'thermal_printer_style'], ['value' => 'office_bill']);
    Configuration::updateOrCreate(['key' => 'company_name'], ['value' => 'Talent Saloon']);
    $this->world->product->update(['name_arabic' => 'قص الشعر']);
    $sale = ($this->ringUp)();

    $html = (new SaleHelper())->saleInvoice($sale->id);

    expect($html)
        ->toContain('Talent Saloon')
        ->toContain('<b>9/1</b>')
        ->toContain('Biller : Haneefa Babu')
        ->toContain(today()->format('d-m-Y').' · '.$sale->created_at->format('H:i'))
        ->toContain('<span>'.$sale->invoice_no.'</span>')
        ->toContain('IBM Plex Sans Arabic')
        ->not->toContain('قص الشعر')
        ->toContain('Sub Total')
        ->toContain('GRAND TOTAL')
        ->not->toContain('Served By')
        ->not->toContain('Paid');
});

it('heads the office bill with the branch when no company name is set', function (): void {
    Configuration::updateOrCreate(['key' => 'thermal_printer_style'], ['value' => 'office_bill']);
    Configuration::where('key', 'company_name')->delete();

    expect((new SaleHelper())->saleInvoice(($this->ringUp)()->id))
        ->toContain('<div class="en">'.$this->world->branch->name.'</div>');
});

it('lets the mobile app pick the style and sends it the bill no', function (): void {
    $this->world->user->givePermissionTo(config('permission.models.permission')::firstOrCreate(['name' => 'configuration.settings', 'guard_name' => 'web']));
    Sanctum::actingAs($this->world->user);

    $this->putJson($this->world->url('/api/v1/settings/sale/print'), ['style' => 'office_bill'])
        ->assertSuccessful()
        ->assertJsonPath('data.print.style', 'office_bill');

    $sale = ($this->ringUp)();
    expect((new SaleResource($sale))->resolve()['bill_no'])->toBe('9/1');
});

it('shows the bill no on the mobile sales list and finds the sale by it', function (): void {
    Sanctum::actingAs($this->world->user);
    ($this->ringUp)();
    $second = ($this->ringUp)();

    $row = $this->getJson($this->world->url('/api/v1/sale?search=9/2'))->assertSuccessful()->json('data.data');

    expect($row)->toHaveCount(1)
        ->and($row[0]['id'])->toBe((string) $second->id)
        ->and($row[0]['bill_no'])->toBe('9/2');
});

it('shows the bill no on the web sale view', function (): void {
    $this->actingAs($this->world->user);
    $sale = ($this->ringUp)();

    Livewire::test(View::class, ['table_id' => $sale->id])
        ->assertSeeHtml('Bill No <b>9/1</b>');
});

it('shows the bill no on the mobile sold-items list and finds the line by it', function (): void {
    Sanctum::actingAs($this->world->user);
    ($this->ringUp)();
    $second = ($this->ringUp)();

    $rows = $this->getJson($this->world->url('/api/v1/sale/items?search=9/2'))->assertSuccessful()->json('data.data');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['sale_id'])->toBe((string) $second->id)
        ->and($rows[0]['bill_no'])->toBe('9/2');
});

/**
 * A till that cannot reach the server prints the Office Bill with the number it
 * counted itself — staff keep those slips to tally their day — so the sync claims
 * that number and the server keeps it whenever it is still free.
 */
describe('a number the till already printed', function (): void {
    beforeEach(function (): void {
        Sanctum::actingAs($this->world->user);

        $this->sync = fn (int $sequence, ?string $date = null) => $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
            'clientUuid' => (string) Str::uuid(),
            'staffBillSequence' => $sequence,
            'staffBillDate' => $date ?? today()->toDateString(),
        ]))->assertSuccessful()->json('data');
    });

    it('is kept when it is free, and later sales count on past it', function (): void {
        ($this->ringUp)();

        $synced = ($this->sync)(3);
        $gapFilled = ($this->sync)(2);

        expect($synced['bill_no'])->toBe('9/3')
            ->and($synced['staff_bill_sequence'])->toBe(3)
            ->and($gapFilled['bill_no'])->toBe('9/2')
            ->and(($this->ringUp)()->bill_no)->toBe('9/4');
    });

    it('gives way to the next number when another sale already holds it', function (): void {
        ($this->ringUp)();
        ($this->ringUp)();

        expect(($this->sync)(2)['bill_no'])->toBe('9/3');
    });

    it('is ignored when it was counted on another business day', function (): void {
        ($this->ringUp)();

        expect(($this->sync)(5, today()->subDay()->toDateString())['bill_no'])->toBe('9/2');
    });

    it('needs the date it was counted on', function (): void {
        $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload(['staffBillSequence' => 3]))
            ->assertUnprocessable();
    });
});

it('tells the till where the biller\'s count stands for the day', function (): void {
    Sanctum::actingAs($this->world->user);
    ($this->ringUp)();
    ($this->ringUp)();
    ($this->ringUp)(date: today()->subDay()->toDateString());

    expect($this->getJson($this->world->url('/api/v1/admin/day-status'))->assertSuccessful()->json('data.staff_bill_sequence'))->toBe(2)
        ->and((new AuthUserResource($this->world->user))->resolve()['staff_bill_sequence'])->toBe(2);
});
