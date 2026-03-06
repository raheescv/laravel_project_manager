<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('ticket::')->prefix('ticket')->controller(TicketController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('ticket.view');
    });
});
