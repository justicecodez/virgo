<?php

use App\Http\Controllers\Guest\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return response()->json(['status'=>true, 'user'=>$request->user()]) ;
})->middleware('auth:sanctum');

Route::group([
    'prefix'=>'guest'
], function() {
    Route::post('/login', [AuthController::class, 'login']);
});
