<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\Owner\AuditLogController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\FightController;
use App\Http\Controllers\Owner\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReceiptController;

/*
|--------------------------------------------------------------------------
| PUBLIC DISPLAY SCREEN
|--------------------------------------------------------------------------
*/

Route::get('/', [DisplayController::class, 'index'])->name('display');

// RECEIPT (public — accessed by Android WebView for printing)
Route::get('/receipt/{reference}', [ReceiptController::class, 'show'])
    ->name('receipt');

/*
|--------------------------------------------------------------------------
| OWNER AUTH (hidden URL)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/manage', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/manage', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/manage/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| OWNER PANEL
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {

        // dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // users
        Route::resource('users', UserController::class)
            ->names('users');

        // fights
        Route::resource('fights', FightController::class)
            ->only(['index', 'show'])
            ->names('fights');

        // audit logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        // live stats endpoint
        Route::get('/stats', function () {
            return response()->json([
                'total_fights' => \App\Models\Fight::count(),
                'total_bets'   => \App\Models\Bet::sum('amount'),
                'total_admins' => \App\Models\User::where('role', 'admin')->count(),
                'total_tellers'=> \App\Models\User::where('role', 'teller')->count(),
            ]);
        })->name('owner.stats');

    });

