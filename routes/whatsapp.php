<?php

use App\Http\Controllers\WhatsappController;
use Illuminate\Support\Facades\Route;

Route::name('whatsapp::')->prefix('whatsapp')
    ->controller(WhatsappController::class)->group(function () {
        Route::get('get-qr', 'index')->name('index');
    });
