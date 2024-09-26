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

Route::post('/login', [AuthController::class, 'login']);
