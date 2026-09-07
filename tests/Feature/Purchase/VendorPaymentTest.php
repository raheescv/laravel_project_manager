<?php

use App\Livewire\Purchase\VendorPayment;
use App\Models\Purchase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Two regressions in the vendor payment modal, both from the LPO work:
 *
 *  - the bill list was narrowed to `Purchase::accepted()`, a status only an
 *    approved LPO bill ever reaches, so a direct purchase (`completed`) never
 *    showed and every vendor read "No outstanding invoices";
 *  - the save was gated on `local purchase order.payments`, while the page
 *    itself is gated on `purchase.payments` — so the form 403'd on submit.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->update(['is_admin' => 0]);

    $this->actAs = function (string ...$permissions) {
        foreach ($permissions as $name) {
            $this->world->user->givePermissionTo(Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
                'tenant_id' => $this->world->tenant->id,
            ]));
        }
        $this->actingAs($this->world->user->fresh());
    };

    // grand_total and balance are STORED GENERATED columns, so bills go in
    // through the query builder.
    $this->makeBill = function (string $status, float $total, float $paid = 0) {
        return DB::table('purchases')->insertGetId([
            'tenant_id' => $this->world->tenant->id,
            'branch_id' => $this->world->branch->id,
            'account_id' => $this->world->accounts['purchase'] ?? $this->world->cashAccountId,
            'invoice_no' => 'PB-'.uniqid(),
            'date' => now()->toDateString(),
            'total' => $total,
            'paid' => $paid,
            'status' => $status,
            'created_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };
});

it('lists a completed direct purchase as an outstanding bill', function (): void {
    ($this->actAs)('purchase.payments');
    $vendorId = $this->world->accounts['purchase'] ?? $this->world->cashAccountId;
    ($this->makeBill)('completed', 500);

    $component = Livewire::test(VendorPayment::class, ['name' => 'Vendor', 'vendor_id' => $vendorId]);

    expect($component->get('data'))->toHaveCount(1)
        ->and($component->get('total')['balance'])->toEqual(500);
});

it('leaves an unposted bill out of the payable list', function (): void {
    ($this->actAs)('purchase.payments');
    $vendorId = $this->world->accounts['purchase'] ?? $this->world->cashAccountId;
    ($this->makeBill)('draft', 500);
    ($this->makeBill)('cancelled', 300);
    ($this->makeBill)('reversed', 200);

    $component = Livewire::test(VendorPayment::class, ['name' => 'Vendor', 'vendor_id' => $vendorId]);

    expect($component->get('data'))->toHaveCount(0);
});

it('accepts an approved LPO bill as payable too', function (): void {
    ($this->actAs)('purchase.payments');
    $vendorId = $this->world->accounts['purchase'] ?? $this->world->cashAccountId;
    ($this->makeBill)('accepted', 400);

    $component = Livewire::test(VendorPayment::class, ['name' => 'Vendor', 'vendor_id' => $vendorId]);

    expect($component->get('data'))->toHaveCount(1);
});

it('lets purchase.payments submit the form', function (): void {
    ($this->actAs)('purchase.payments');

    Livewire::test(VendorPayment::class)->call('save')->assertOk();
});

it('lets local purchase order.payments submit the form', function (): void {
    ($this->actAs)('local purchase order.payments');

    Livewire::test(VendorPayment::class)->call('save')->assertOk();
});

it('still forbids a user holding neither payment right', function (): void {
    ($this->actAs)('purchase.view');

    Livewire::test(VendorPayment::class)->call('save')->assertForbidden();
});
