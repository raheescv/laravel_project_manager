<?php

use App\Http\Controllers\PurchaseController;
use Illuminate\View\View;

it('uses the dedicated purchase view shell for completed purchase details', function (): void {
    $response = app(PurchaseController::class)->view(123);

    expect($response)->toBeInstanceOf(View::class)
        ->and($response->name())->toBe('purchase.view')
        ->and($response->getData()['id'])->toBe(123);
});
