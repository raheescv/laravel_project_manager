<?php

use App\Http\Controllers\Api\V1\TechnicianChecklistController;
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

            // RentOut hand-over checklist — scoped to rent-outs the user coordinates.
            Route::controller(TechnicianChecklistController::class)->name('api.v1.technician.checklists.')->group(function () {
                Route::get('/checklists', 'index')->name('index');
                Route::get('/checklists/{rentOut}', 'show')->whereNumber('rentOut')->name('show');
                Route::get('/checklists/{rentOut}/pdf', 'pdf')->whereNumber('rentOut')->name('pdf');
                Route::patch('/checklists/{rentOut}/lines/{line}', 'updateLine')->whereNumber(['rentOut', 'line'])->name('lines.update');
                Route::post('/checklists/{rentOut}/lines/{line}/photo', 'linePhoto')->whereNumber(['rentOut', 'line'])->name('lines.photo');
                Route::post('/checklists/{rentOut}/lines/mark-ok', 'markOk')->whereNumber('rentOut')->name('lines.mark-ok');
                Route::post('/checklists/{rentOut}/signatures', 'sign')->whereNumber('rentOut')->name('sign');
                Route::post('/checklists/{rentOut}/seal', 'seal')->whereNumber('rentOut')->name('seal');
                Route::post('/checklists/{rentOut}/fixtures', 'storeFixture')->whereNumber('rentOut')->name('fixtures.store');
                Route::post('/checklists/{rentOut}/fixtures/{area}/sign', 'signFixture')->whereNumber(['rentOut', 'area'])->name('fixtures.sign');
                Route::patch('/fixture-entries/{entry}', 'updateFixture')->whereNumber('entry')->name('fixtures.update');
                Route::post('/fixture-entries/{entry}/photo', 'fixturePhoto')->whereNumber('entry')->name('fixtures.photo');
                Route::delete('/fixture-entries/{entry}', 'deleteFixture')->whereNumber('entry')->name('fixtures.delete');
            });
        });
    });
});
