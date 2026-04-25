<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BetController;
use App\Http\Controllers\Api\CashRequestController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\FightController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\RunnerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH (public)
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login']);

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
    | CASH OUT (Teller only)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:teller')->group(function () {
        Route::get('/payout/{reference}', [PayoutController::class, 'show']);
        Route::post('/payout/{reference}', [PayoutController::class, 'confirm']);
    });

    /*
    |----------------------------------------------------------------------
    | CASH REQUESTS (Teller, Runner, Admin, Owner)
    |----------------------------------------------------------------------
    */
    // Teller creates cash request
    Route::middleware('role:teller')->group(function () {
        Route::post('/cash-request', [CashRequestController::class, 'store']);
    });

    // Runner and Owner view and manage cash requests
    Route::middleware(['checkRoles:runner|admin|owner'])->group(function () {
        Route::get('/cash-requests', [CashRequestController::class, 'index']);
        Route::get('/cash-request/{id}', [CashRequestController::class, 'show']);
        Route::patch('/cash-request/{id}/approve', [CashRequestController::class, 'approve']);
        Route::patch('/cash-request/{id}/complete', [CashRequestController::class, 'complete']);
        Route::patch('/cash-request/{id}/reject', [CashRequestController::class, 'reject']);
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

});
