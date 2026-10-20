<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AbsensiController;
use App\Http\Controllers\Api\V1\DashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']); 

     Route::middleware('auth:sanctum')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/absensi', [AbsensiController::class, 'index']);
        Route::get('/dashboard/stats', [DashboardController::class, 'index']);
        Route::get('/dashboard/server', [DashboardController::class, 'serverStats']); 

         Route::middleware('admin')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/dashboard/stats', [DashboardController::class, 'index']);
            Route::get('/dashboard/server', [DashboardController::class, 'serverStats']);
            Route::get('/analytics/online', [\App\Http\Controllers\Api\V1\AnalyticsController::class, 'onlineVisitors']);
        });
    });
    
});