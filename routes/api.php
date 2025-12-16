<?php

use App\Http\Controllers\Auth\OrderController;
use App\Http\Controllers\Guest\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group([
    'prefix' => 'guest'
], function () {
    Route::post('/login', [AuthController::class, 'login']);
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my/orders', [OrderController::class, 'myOrders']);
    Route::get('/user', function (Request $request) {
        return response()->json(['status' => true, 'user' => $request->user()]);
    });
    Route::get('/orders/symbol/{symbol}', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);

});
