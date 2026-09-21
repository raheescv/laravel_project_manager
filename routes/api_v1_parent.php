<?php

use App\Http\Controllers\Api\V1\ParentPortalController;
use App\Http\Controllers\Api\V1\ParentPreOrderController;
use App\Http\Middleware\AuthenticateParent;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\IdentifyTenant;
use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 — Parent portal routes
|--------------------------------------------------------------------------
|
| Everything the standalone parent_portal app (School module only) talks to.
| The tenant MUST resolve — from the school's host, or the X-Tenant-Subdomain
| header / ?tenant= hint on a bare IP. Parents authenticate with a bearer token
| from `login` / `set-password`; AuthenticateParent accepts nothing else, and
| the `sanctum` guard staff endpoints use refuses a parent's token.
|
*/

Route::prefix('v1/parent')
    ->middleware([IdentifyTenant::class.':required', EnsureModuleEnabled::class.':'.ModuleAccess::SCHOOL])
    ->controller(ParentPortalController::class)
    ->name('api.v1.parent.')
    ->group(function (): void {
        Route::get('school', 'school')->name('school');

        Route::post('login', 'login')->middleware('throttle:20,1')->name('login');
        Route::post('forgot-password', 'forgotPassword')->middleware('throttle:10,1')->name('password.forgot');
        Route::get('set-password/{token}', 'passwordLink')->middleware('throttle:30,1')->name('password.link');
        Route::post('set-password', 'setPassword')->middleware('throttle:10,1')->name('password.set');

        // QPay posts the payment result here from the parent's browser — no token.
        // Authenticated by QPay's secure hash and re-checked with its inquiry API.
        Route::post('qpay/return', 'qpayReturn')->middleware('throttle:60,1')->name('qpay.return');

        // The Mastercard Gateway sends the parent's browser back here from the credit
        // card page — paid, declined for the last time, or cancelled. No token: the
        // browser's word is never taken, the order is read back from the gateway.
        Route::match(['get', 'post'], 'mpgs/return/{pun}', 'mpgsReturn')->where('pun', '[A-Za-z0-9]{1,40}')->middleware('throttle:60,1')->name('mpgs.return');
        Route::match(['get', 'post'], 'mpgs/cancel/{pun}', 'mpgsCancel')->where('pun', '[A-Za-z0-9]{1,40}')->middleware('throttle:60,1')->name('mpgs.cancel');

        Route::middleware(AuthenticateParent::class)->group(function (): void {
            Route::get('me', 'me')->name('me');
            Route::post('password', 'changePassword')->middleware('throttle:10,1')->name('password.change');
            Route::post('logout', 'logout')->name('logout');

            Route::get('students', 'students')->name('students.index');
            Route::prefix('students/{account}')->whereNumber('account')->name('students.')->group(function (): void {
                Route::get('/', 'student')->name('show');
                Route::get('bills', 'bills')->name('bills');
                Route::get('bills/{sale}', 'bill')->whereNumber('sale')->name('bill');
                Route::get('statement', 'statement')->name('statement');
                Route::post('card/block', 'blockCard')->middleware('throttle:10,1')->name('card.block');
                Route::post('topups', 'startTopup')->middleware('throttle:10,1')->name('topups.store');
            });

            Route::get('topups/{pun}', 'topup')->where('pun', '[A-Za-z0-9]{1,40}')->name('topups.show');
        });
    });

// Canteen pre-orders: items the till adds to the cart when the child's card is tapped.
Route::prefix('v1/parent')
    ->middleware([IdentifyTenant::class.':required', EnsureModuleEnabled::class.':'.ModuleAccess::SCHOOL, AuthenticateParent::class])
    ->controller(ParentPreOrderController::class)
    ->name('api.v1.parent.pre-orders.')
    ->group(function (): void {
        Route::get('pre-order-menu', 'menu')->name('menu');

        Route::prefix('students/{account}/pre-orders')->whereNumber('account')->middleware('throttle:60,1')->group(function (): void {
            Route::get('/', 'schedule')->name('schedule');

            Route::put('weekly', 'saveWeekly')->name('weekly.save');
            Route::post('weekly/pause', 'pauseWeekly')->name('weekly.pause');
            Route::post('weekly/resume', 'resumeWeekly')->name('weekly.resume');
            Route::delete('weekly', 'deleteWeekly')->name('weekly.delete');

            Route::put('days/{date}', 'saveDay')->where('date', '\d{4}-\d{2}-\d{2}')->name('days.save');
            Route::post('days/{date}/skip', 'skipDay')->where('date', '\d{4}-\d{2}-\d{2}')->name('days.skip');
            Route::delete('days/{date}', 'clearDay')->where('date', '\d{4}-\d{2}-\d{2}')->name('days.clear');
        });
    });
