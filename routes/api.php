<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Constants\HttpStatusCodes;
use App\Http\Middleware\CheckRole;

use App\Http\Controllers\{
    AuthController,
    RestaurantController
};

Route::fallback(function() {
    return response()->json([
        'status_code'  => HttpStatusCodes::HTTP_NOT_FOUND,
        'error'   => true,
        'message' => 'URL Not Found'
    ],HttpStatusCodes::HTTP_NOT_FOUND);
});

Route::get('/healthz', function () {
    return 1;
});

Route::controller(RestaurantController::class)->group(function(){
    Route::get('/open-data', 'index');
});

Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::group([
        'prefix' => 'restaurants', 
    ], function() {
        Route::controller(RestaurantController::class)->group(function(){
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'store');
            Route::put('/{id}', 'update');
            Route::delete('/{id}', 'destroy');
        });
    });
});