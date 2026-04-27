<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BetController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\FightController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\RunnerController;
use App\Http\Controllers\Api\RunnerAssistanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH (public)
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login']);

// Get system settings (public - no auth required)
Route::get('/settings', function () {
    $settings = \App\Models\Setting::first();
    return response()->json([
        'display_title' => $settings?->display_title ?? 'SABONG BETTING SYSTEM',
    ]);
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED (any valid token)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // current fight — accessible by all roles
    Route::get('/fight/current', [FightController::class, 'current']);

    // device management — accessible by all roles
    Route::get('/devices', [DeviceController::class, 'index']);
    Route::delete('/devices/{device}', [DeviceController::class, 'revoke']);
    Route::post('/devices/revoke-all', [DeviceController::class, 'revokeAll']);

    /*
    |----------------------------------------------------------------------
    | ADMIN only
    |----------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        Route::post('/fight', [FightController::class, 'store']);
        Route::post('/fight/reset', [FightController::class, 'reset']);
        Route::put('/fight/{fight}/status', [FightController::class, 'updateStatus']);
        Route::post('/fight/{fight}/winner', [FightController::class, 'declareWinner']);
        Route::put('/fight/{fight}/side-status', [FightController::class, 'updateSideStatus']);
        Route::put('/fight/{fight}/all-side-status', [FightController::class, 'allSideStatus']);
        Route::post('/fight/{fight}/finalize', [FightController::class, 'finalizeBet']);
        Route::get('/fight/history', [FightController::class, 'history']);
    });

    /*
    |----------------------------------------------------------------------
    | CASH IN (Admin and Teller)
    |----------------------------------------------------------------------
    */
    Route::middleware(['checkRoles:admin|teller', 'ability:cashin'])->group(function () {
        Route::post('/bet', [BetController::class, 'store']);
        Route::get('/bet/history', [BetController::class, 'index']);
        Route::get('/bet/{reference}', [BetController::class, 'show']);
    });

    /*
    |----------------------------------------------------------------------
    | NOTIFICATIONS
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/notifications', [NotificationController::class, 'store']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/notifications', [NotificationController::class, 'clear']);
    });

    /*
    |----------------------------------------------------------------------
    | RUNNER ASSISTANCE (Teller and Runner)
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        // Teller requests assistance
        Route::post('/assistance/request', [RunnerAssistanceController::class, 'request']);

        // Runner accepts assistance
        Route::post('/assistance/accept/{teller_id}', [RunnerAssistanceController::class, 'accept']);
    });

    /*
    |----------------------------------------------------------------------
    | RUNNER only
    |----------------------------------------------------------------------
    */
    Route::middleware('role:runner')->group(function () {
        Route::get('/runner/tellers', [RunnerController::class, 'getTellersCashStatus']);
        Route::get('/runner/history', [RunnerController::class, 'getHistory']);
        Route::post('/runner/transaction', [RunnerController::class, 'createTransaction']);
    });

    /*
    |----------------------------------------------------------------------
    | DEBUG/TEST endpoints
    |----------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/test/create-notification', [\App\Http\Controllers\Api\TestController::class, 'createTestNotification']);
        Route::get('/test/notifications', [\App\Http\Controllers\Api\TestController::class, 'listNotifications']);
    });

});
