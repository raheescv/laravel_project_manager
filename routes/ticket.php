<?php

use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketImportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('ticket::')->prefix('ticket')->group(function (): void {
        Route::get('', [TicketController::class, 'index'])->name('index')->can('ticket.view');
        Route::get('import', [TicketController::class, 'import'])->name('import')->can('ticket.import');

        Route::name('api::')->prefix('api')->group(function (): void {
            Route::controller(TicketController::class)->group(function (): void {
                Route::get('board', 'board')->name('board')->can('ticket.view');
                Route::post('', 'store')->name('store')->can('ticket.create');
                Route::get('{id}', 'show')->whereNumber('id')->name('show')->can('ticket.view');
                Route::post('{id}', 'update')->whereNumber('id')->name('update')->can('ticket.edit');
                Route::patch('{id}/status', 'status')->whereNumber('id')->name('status')->can('ticket.edit');
                Route::delete('{id}', 'destroy')->whereNumber('id')->name('destroy')->can('ticket.delete');
                Route::delete('{id}/attachment/{attachmentId}', 'destroyAttachment')->whereNumber(['id', 'attachmentId'])->name('attachment.destroy')->can('ticket.edit');
            });

            Route::controller(TicketCommentController::class)->name('comment::')->group(function (): void {
                Route::post('{id}/comment', 'store')->whereNumber('id')->name('store')->can('ticket.comment');
                Route::put('{id}/comment/{commentId}', 'update')->whereNumber(['id', 'commentId'])->name('update')->can('ticket.comment');
                Route::delete('{id}/comment/{commentId}', 'destroy')->whereNumber(['id', 'commentId'])->name('destroy')->can('ticket.comment');
            });

            Route::controller(TicketImportController::class)->name('import::')->prefix('import')->group(function (): void {
                Route::get('template', 'template')->name('template')->can('ticket.import');
                Route::post('upload', 'upload')->name('upload')->can('ticket.import');
                Route::post('sheet', 'sheet')->name('sheet')->can('ticket.import');
                Route::post('review', 'review')->name('review')->can('ticket.import');
                Route::post('commit', 'commit')->name('commit')->can('ticket.import');
            });
        });
    });
});
