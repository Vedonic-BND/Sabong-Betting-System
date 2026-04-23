<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BetController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\FightController;
use App\Http\Controllers\Api\PayoutController;
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
        Route::put('/fight/{fight}/status', [FightController::class, 'updateStatus']);
        Route::post('/fight/{fight}/winner', [FightController::class, 'declareWinner']);
        Route::put('/fight/{fight}/side-status', [FightController::class, 'updateSideStatus']);
        Route::put('/fight/{fight}/all-side-status', [FightController::class, 'allSideStatus']);
        Route::post('/fight/{fight}/finalize', [FightController::class, 'finalizeBet']);
        Route::get('/fight/history', [FightController::class, 'history']);
        Route::post('/bet', [BetController::class, 'store']);
    });

    /*
    |----------------------------------------------------------------------
    | TELLER Cash In only
    |----------------------------------------------------------------------
    */
    Route::middleware('role:teller')->group(function () {
        Route::post('/bet', [BetController::class, 'store']);
    });

    /*
    |----------------------------------------------------------------------
    | TELLER Cash Out only
    |----------------------------------------------------------------------
    */
    Route::middleware('role:teller')->group(function () {
        Route::get('/payout/{reference}', [PayoutController::class, 'show']);
        Route::post('/payout/{reference}', [PayoutController::class, 'confirm']);
    });

});
