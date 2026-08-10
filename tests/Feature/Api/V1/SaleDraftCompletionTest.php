<?php

use App\Models\Inventory;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\Sale;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * Finishing a parked ticket from the app.
 *
 * A draft is a sale that has been written down but not rung up: no stock has
 * moved and nothing has reached the ledger. The app can now finish one in the
 * same call that saves the cashier's last edits, which is the moment those
 * postings happen — so what these tests pin is that the postings follow the
 * status, once, and only in the one direction that is offered.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Sanctum::actingAs($this->world->user);

    $this->stock = fn () => (float) Inventory::withoutGlobalScopes()
        ->where('product_id', $this->world->product->id)
        ->where('branch_id', $this->world->branch->id)
        ->value('quantity');

    $this->ledgerLines = fn (Sale $sale) => JournalEntry::withoutGlobalScopes()
        ->where('model', 'Sale')->where('model_id', $sale->id)->count();

    // A ticket parked by the app: POST /sale carrying status=draft.
    $this->parkDraft = function (array $overrides = []): Sale {
        $this->postJson(
            $this->world->url('/api/v1/sale'),
            $this->world->salePayload(array_merge(['status' => 'draft'], $overrides)),
        )->assertSuccessful();

        return Sale::withoutGlobalScopes()->latest('id')->first();
    };

    // What the app sends back when that draft is re-opened and saved. Lines keep
    // their sale_item id so the existing rows are patched rather than duplicated.
    $this->editPayload = fn (Sale $sale, array $overrides = []) => array_merge([
        'customerName' => 'Walk-in Customer',
        'items' => $sale->items->map(fn ($item) => [
            'id' => (int) $item->id,
            'productId' => (int) $item->product_id,
            'quantity' => (float) $item->quantity,
            'unitPrice' => (float) $item->unit_price,
            'discount' => (float) $item->discount,
        ])->all(),
        'discount' => 0,
        'tip' => 0,
        'paymentMethod' => 'Cash',
        'totalPayment' => (float) $sale->grand_total,
    ], $overrides);
});

it('parks a draft without moving stock or posting to the ledger', function (): void {
    $sale = ($this->parkDraft)();

    expect($sale->status)->toBe('draft')
        ->and(($this->stock)())->toBe(100.0)
        ->and(($this->ledgerLines)($sale))->toBe(0);
});

it('completes a parked draft when the app sends status completed', function (): void {
    $sale = ($this->parkDraft)();

    $response = $this->putJson(
        $this->world->url('/api/v1/sale/'.$sale->id),
        ($this->editPayload)($sale->load('items'), ['status' => 'completed']),
    )->assertSuccessful();

    expect($response->json('data.status'))->toBe('completed');

    $sale->refresh();
    expect($sale->status)->toBe('completed')
        // The postings a draft had been holding back both land now.
        ->and(($this->stock)())->toBe(99.0)
        ->and(($this->ledgerLines)($sale))->toBeGreaterThan(0)
        ->and((float) $sale->paid)->toBe(50.0)
        ->and((float) $sale->balance)->toBe(0.0);
});

it('keeps the sale parked when the app saves an edit without a status', function (): void {
    $sale = ($this->parkDraft)();

    $this->putJson(
        $this->world->url('/api/v1/sale/'.$sale->id),
        ($this->editPayload)($sale->load('items'), [
            'items' => [[
                'id' => (int) $sale->items->first()->id,
                'productId' => (int) $this->world->product->id,
                'quantity' => 3,
                'unitPrice' => 50,
                'discount' => 0,
            ]],
            'totalPayment' => 150,
        ]),
    )->assertSuccessful();

    $sale->refresh();
    // The edit applied — but it is still a draft, so nothing was rung up.
    expect((float) $sale->gross_amount)->toBe(150.0)
        ->and($sale->status)->toBe('draft')
        ->and(($this->stock)())->toBe(100.0)
        ->and(($this->ledgerLines)($sale))->toBe(0);
});

it('posts one journal document, not one per save, when a draft is completed', function (): void {
    $sale = ($this->parkDraft)();

    $this->putJson(
        $this->world->url('/api/v1/sale/'.$sale->id),
        ($this->editPayload)($sale->load('items'), ['status' => 'completed']),
    )->assertSuccessful();

    // The sale line, the cost of goods sold and the cash receipt all belong to
    // one journal — a second document here would mean the entry was posted twice.
    expect(Journal::withoutGlobalScopes()->where('model', 'Sale')->where('model_id', $sale->id)->count())->toBe(1);
});

it('still requires the completed-edit permission once the draft has been rung up', function (): void {
    $sale = ($this->parkDraft)();
    $payload = ($this->editPayload)($sale->load('items'), ['status' => 'completed']);

    // Completing a draft needs no special permission — nothing is being unwound.
    $this->putJson($this->world->url('/api/v1/sale/'.$sale->id), $payload)->assertSuccessful();

    // Editing it afterwards does: that reverses posted stock and ledger entries,
    // which is exactly what `sale.edit completed` gates on the web.
    $this->putJson(
        $this->world->url('/api/v1/sale/'.$sale->id),
        ($this->editPayload)($sale->fresh()->load('items'), ['status' => 'completed']),
    )->assertStatus(422)->assertJsonPath('success', false);
});

it('refuses to move a completed sale back to draft', function (): void {
    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload())->assertSuccessful();
    $sale = Sale::withoutGlobalScopes()->latest('id')->first();
    $stockAfterSale = ($this->stock)();

    $this->putJson(
        $this->world->url('/api/v1/sale/'.$sale->id),
        ($this->editPayload)($sale->load('items'), ['status' => 'draft']),
    )->assertStatus(422);

    // Refused before anything was written — un-completing would have reversed the
    // stock and the ledger behind an ordinary-looking document.
    expect($sale->refresh()->status)->toBe('completed')
        ->and(($this->stock)())->toBe($stockAfterSale)
        ->and(($this->ledgerLines)($sale))->toBeGreaterThan(0);
});

it('takes the stock from the branch that raised the draft, not the till completing it', function (): void {
    $sale = ($this->parkDraft)();

    // The cashier is now signed in at another branch — a parked ticket is very
    // often picked up somewhere else, which is what makes this reachable.
    $second = $this->world->addBranch();
    $this->world->user->forceFill(['default_branch_id' => $second->id])->save();

    $this->putJson(
        $this->world->url('/api/v1/sale/'.$sale->id),
        ($this->editPayload)($sale->load('items'), ['status' => 'completed']),
    )->assertSuccessful();

    $stockAt = fn (int $branchId) => (float) Inventory::withoutGlobalScopes()
        ->where('product_id', $this->world->product->id)
        ->where('branch_id', $branchId)
        ->value('quantity');

    // The sale, its journal and its stock movement all have to name one branch.
    expect($sale->refresh()->branch_id)->toBe($this->world->branch->id)
        ->and($stockAt($this->world->branch->id))->toBe(99.0)
        ->and($stockAt($second->id))->toBe(100.0)
        ->and(Journal::withoutGlobalScopes()->where('model', 'Sale')->where('model_id', $sale->id)->value('branch_id'))
        ->toBe($this->world->branch->id);
});

it('keeps the account a sale is already settled against when the app re-sends the method by name', function (): void {
    // Two configured accounts both answer to "Card". The app sends the MODE the
    // cashier sees ("Card"), not an account id, so resolving that name by first
    // match would move a Mastercard sale — and its ledger entry — onto Visa.
    $visa = $this->world->addPaymentMethod('Visa Card');
    $mastercard = $this->world->addPaymentMethod('Mastercard');

    $sale = ($this->parkDraft)(['paymentMethod' => 'custom', 'payments' => [
        ['payment_method_id' => $mastercard, 'amount' => 50],
    ]]);

    expect((int) $sale->payments()->value('payment_method_id'))->toBe($mastercard);

    $this->putJson(
        $this->world->url('/api/v1/sale/'.$sale->id),
        ($this->editPayload)($sale->load('items'), ['paymentMethod' => 'Card', 'status' => 'completed']),
    )->assertSuccessful();

    $sale->refresh()->load('payments');
    expect((int) $sale->payments->first()->payment_method_id)->toBe($mastercard)
        ->and((int) $sale->payments->first()->payment_method_id)->not->toBe($visa)
        ->and($sale->payment_method_name)->toBe('Mastercard');
});

it('returns the settling payment with its account, so the app can restore the Cash button', function (): void {
    // The app has no "Cash mode" column to read back: a Cash sale is stored as an
    // ordinary payment row against the configured Cash account, exactly like a
    // split payment. This is the shape CartCubit._seedPayments reads.
    $response = $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload())
        ->assertSuccessful();

    expect($response->json('data.payments'))->toHaveCount(1)
        ->and($response->json('data.payments.0.payment_method_id'))->toBe($this->world->cashAccountId)
        ->and($response->json('data.payments.0.method'))->toBe('Cash')
        ->and($response->json('data.payments.0.amount'))->toEqual($response->json('data.summary.grand_total'));
});
