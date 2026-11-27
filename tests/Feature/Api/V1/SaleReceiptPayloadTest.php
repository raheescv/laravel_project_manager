<?php

use App\Helpers\SaleHelper;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PosWorld;

/**
 * The mobile sale payload carries what the web invoice prints, so the app's
 * on-device receipt is the same document: the variant label on the line, the
 * web's line amounts, the total barcode, the QR link and who served it.
 */
it('hands the app everything the web invoice prints', function (): void {
    $world = PosWorld::create(price: 400);
    $world->product->update(['name' => 'mulak pack', 'variant_value' => 'Soda', 'size' => 'Mango', 'description' => 'Fresh mint drink pack, 100 grams each']);
    Sanctum::actingAs($world->user);

    $payload = $world->salePayload([
        'items' => [['productId' => $world->product->id, 'quantity' => 1, 'unitPrice' => 400, 'discount' => 0]],
        'totalPayment' => 400,
    ]);
    $data = $this->postJson($world->url('/api/v1/sale'), $payload)->assertSuccessful()->json('data');

    expect($data['items'][0]['name'])->toBe('mulak pack - Soda / Mango')
        ->and($data['items'][0]['print_name'])->toBe('mulak pack - Soda / Mango')
        ->and($data['items'][0]['description'])->toBe(Str::limit('Fresh mint drink pack, 100 grams each', 30))
        ->and($data['items'][0]['line_total'])->toEqual(400)
        ->and($data['payments'][0])->toHaveKey('alias')
        ->and($data['receipt']['barcode'])->toBe(SaleHelper::barcodeString('040000'))
        ->and($data['receipt']['qr'])->toEndWith('/invoice/'.$data['id'])
        ->and($data['receipt'])->toHaveKeys(['branch_location', 'gst_no', 'served_by', 'updated_at', 'currency_code', 'currency_symbol']);
});
