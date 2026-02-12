<?php

use App\Http\Controllers\IssueController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::name('issue::')->prefix('issue')->controller(IssueController::class)->group(function (): void {
        Route::get('', 'index')->name('index')->can('issue.view');
        Route::get('create/{type}', 'page')->whereIn('type', ['issue', 'return'])->name('create')->can('issue.create');
        Route::get('edit/{id}', 'edit')->name('edit')->can('issue.edit');
        Route::get('view/{id}', 'view')->name('view')->can('issue.view');
        Route::get('print/{id}', 'print')->name('print')->can('issue.print');
        Route::get('list', 'get')->name('list')->can('issue.view');
    });
});
