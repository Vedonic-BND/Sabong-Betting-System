<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\Owner\AuditLogController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\FightController;
use App\Http\Controllers\Owner\UserController;
use App\Http\Controllers\Owner\SettingsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\Owner\ExportController;
use App\Http\Controllers\Owner\ProfileController;

/*
|--------------------------------------------------------------------------
| PUBLIC DISPLAY SCREEN
|--------------------------------------------------------------------------
*/

Route::get('/', [DisplayController::class, 'index'])->name('display');

// RECEIPT (public — accessed by Android WebView for printing)
Route::get('/receipt/{reference}', [ReceiptController::class, 'show'])
    ->name('receipt');

Route::get('/payout-receipt/{reference}', [ReceiptController::class, 'payout'])
    ->name('payout-receipt');

/*
|--------------------------------------------------------------------------
| OWNER AUTH (hidden URL)
|--------------------------------------------------------------------------
*/

Route::get('/manage', function () {
    // If user is authenticated, redirect to owner dashboard
    if (auth()->check()) {
        return redirect()->route('owner.dashboard');
    }
    // Otherwise show login form
    return app(\App\Http\Controllers\Auth\AuthenticatedSessionController::class)->create();
})->name('login');

Route::post('/manage', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest');

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

        // profile
        Route::get('/profile', [ProfileController::class, 'show'])
            ->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
            ->name('profile.update-password');

        // settings
        Route::get('/settings', [SettingsController::class, 'show'])
            ->name('settings.show');
        Route::put('/settings', [SettingsController::class, 'update'])
            ->name('settings.update');

        // fights - export route must be before resource route
        Route::get('/fights/export', [ExportController::class, 'fights'])
            ->name('fights.export');

        Route::resource('fights', FightController::class)
            ->only(['index', 'show'])
            ->names('fights');

        // audit logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        // reports
        Route::get('/audit-logs/export', [ExportController::class, 'auditLogs'])
            ->name('audit-logs.export');

        // notifications
        Route::get('/notifications/export', [\App\Http\Controllers\Owner\NotificationController::class, 'export'])
            ->name('notifications.export');
        Route::get('/notifications', [\App\Http\Controllers\Owner\NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::patch('/notifications/{notification}/read', [\App\Http\Controllers\Owner\NotificationController::class, 'markAsRead'])
            ->name('notifications.mark-read');
        Route::delete('/notifications/{notification}', [\App\Http\Controllers\Owner\NotificationController::class, 'delete'])
            ->name('notifications.delete');
        Route::delete('/notifications', [\App\Http\Controllers\Owner\NotificationController::class, 'clear'])
            ->name('notifications.clear');
        Route::post('/assign-runner/{tellerId}', [\App\Http\Controllers\Owner\NotificationController::class, 'assignRunner'])
            ->name('assign-runner');

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

