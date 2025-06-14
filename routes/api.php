<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Transaction\UserWorkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::prefix('transactions')->group(function () {
        Route::prefix('user-work')->group(function () {
            Route::get('/', [UserWorkController::class, 'index']);
            Route::get('/actual-work', [UserWorkController::class, 'actuallyWork']);
        });
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/{id}', [TransactionController::class, 'show']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::put('/{id}', [TransactionController::class, 'update']);
        Route::delete('/{id}', [TransactionController::class, 'destroy']);
    });
});
