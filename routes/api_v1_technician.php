<?php

use App\Http\Controllers\Api\V1\TechnicianController;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 — Technician routes
|--------------------------------------------------------------------------
|
| The technician maintenance workflow for the standalone "Astra Technician"
| app. Mirrors App\Livewire\Maintenance\Complaint. Every route is tenant-scoped
| (IdentifyTenant), Sanctum-authenticated, and further filtered to complaints
| assigned to the authenticated technician (technician_id = auth id) inside the
| actions. No mobile permission gates — assignment to the complaint is the
| only authorization.
|
*/

Route::prefix('v1')->middleware(IdentifyTenant::class)->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('technician')->group(function () {
            Route::get('/dashboard', [TechnicianController::class, 'dashboard'])->name('api.v1.technician.dashboard');
            Route::get('/complaints', [TechnicianController::class, 'index'])->name('api.v1.technician.complaints.index');
            Route::get('/complaints/{complaint}', [TechnicianController::class, 'show'])->whereNumber('complaint')->name('api.v1.technician.complaints.show');
            Route::match(['put', 'patch'], '/complaints/{complaint}', [TechnicianController::class, 'update'])->whereNumber('complaint')->name('api.v1.technician.complaints.update');
            Route::post('/complaints/{complaint}/complete', [TechnicianController::class, 'complete'])->whereNumber('complaint')->name('api.v1.technician.complaints.complete');

            // Supply items
            Route::post('/complaints/{complaint}/supply-items', [TechnicianController::class, 'storeSupplyItem'])->whereNumber('complaint')->name('api.v1.technician.supply-items.store');
            Route::match(['put', 'patch'], '/supply-items/{item}', [TechnicianController::class, 'updateSupplyItem'])->whereNumber('item')->name('api.v1.technician.supply-items.update');
            Route::delete('/supply-items/{item}', [TechnicianController::class, 'deleteSupplyItem'])->whereNumber('item')->name('api.v1.technician.supply-items.delete');

            // Notes
            Route::post('/complaints/{complaint}/notes', [TechnicianController::class, 'storeNote'])->whereNumber('complaint')->name('api.v1.technician.notes.store');
            Route::delete('/notes/{note}', [TechnicianController::class, 'deleteNote'])->whereNumber('note')->name('api.v1.technician.notes.delete');

            // Attachments
            Route::post('/complaints/{complaint}/attachments', [TechnicianController::class, 'storeAttachments'])->whereNumber('complaint')->name('api.v1.technician.attachments.store');
            Route::delete('/attachments/{attachment}', [TechnicianController::class, 'deleteAttachment'])->whereNumber('attachment')->name('api.v1.technician.attachments.delete');
        });
    });
});
