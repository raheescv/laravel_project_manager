<?php

use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

Route::name('_whatsapp::')
    ->prefix('whatsapp')
    ->group(function (): void {
        Route::get('webhook', [WhatsappWebhookController::class, 'verify'])->name('webhook.verify');
        Route::post('webhook', [WhatsappWebhookController::class, 'receive'])->name('webhook.receive');
    });
