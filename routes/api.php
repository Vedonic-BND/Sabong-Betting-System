<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BetController;
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

    /*
    |----------------------------------------------------------------------
    | ADMIN only
    |----------------------------------------------------------------------
    */
    Route::middleware('ability:admin')->group(function () {
        Route::post('/fight', [FightController::class, 'store']);
        Route::put('/fight/{fight}/status', [FightController::class, 'updateStatus']);
        Route::post('/fight/{fight}/winner', [FightController::class, 'declareWinner']);
        Route::put('/fight/{fight}/side-status', [FightController::class, 'updateSideStatus']);
        Route::post('/fight/{fight}/finalize', [FightController::class, 'finalizeBet']);
        Route::get('/fight/history', [FightController::class, 'history']);
        Route::post('/bet', [BetController::class, 'store']);
    });

    /*
    |----------------------------------------------------------------------
    | TELLER Cash In only
    |----------------------------------------------------------------------
    */
    Route::middleware('ability:cashin')->group(function () {
        Route::post('/bet', [BetController::class, 'store']);
    });

    /*
    |----------------------------------------------------------------------
    | TELLER Cash Out only
    |----------------------------------------------------------------------
    */
    Route::middleware('ability:cashout')->group(function () {
        Route::get('/payout/{reference}', [PayoutController::class, 'show']);
        Route::post('/payout/{reference}', [PayoutController::class, 'confirm']);
    });

});
