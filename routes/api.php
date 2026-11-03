<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Engineer\ClientController;
use App\Http\Controllers\Api\Engineer\DashboardController;
use App\Http\Controllers\Api\Engineer\FarmController as EngineerFarmController;
use App\Http\Controllers\Api\Engineer\TaskController as EngineerTaskController;
use App\Http\Controllers\Api\Engineer\VisitController as EngineerVisitController;
use App\Http\Controllers\Api\Farmer\DashboardController as FarmerDashboardController;
use App\Http\Controllers\Api\Farmer\FarmController as FarmerFarmController;
use App\Http\Controllers\Api\Farmer\TaskController as FarmerTaskController;
use App\Http\Controllers\Api\Farmer\VisitController as FarmerVisitController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| المصادقة (رقم موبايل + كود تفعيل)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/otp/request', [AuthController::class, 'requestOtp']);
    Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {

    // إشعارات مشتركة (كل حساب بيشوف إشعاراته بس)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    /*
    |----------------------------------------------------------------------
    | المهندس
    |----------------------------------------------------------------------
    */
    Route::middleware('role:engineer')->prefix('engineer')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::get('/clients', [ClientController::class, 'index']);
        Route::post('/clients', [ClientController::class, 'store']);
        Route::get('/clients/{client}', [ClientController::class, 'show']);

        Route::post('/clients/{client}/farms', [EngineerFarmController::class, 'store']);
        Route::get('/farms/{farm}', [EngineerFarmController::class, 'show']);
        Route::put('/farms/{farm}', [EngineerFarmController::class, 'update']);

        Route::get('/tasks', [EngineerTaskController::class, 'index']);
        Route::post('/farms/{farm}/tasks', [EngineerTaskController::class, 'store']);
        Route::get('/tasks/{task}', [EngineerTaskController::class, 'show']);
        Route::post('/tasks/{task}/approve', [EngineerTaskController::class, 'approve']);
        Route::post('/tasks/{task}/reject', [EngineerTaskController::class, 'reject']);

        Route::get('/visits', [EngineerVisitController::class, 'index']);
        Route::post('/farms/{farm}/visits', [EngineerVisitController::class, 'store']);
        Route::get('/visits/{visit}', [EngineerVisitController::class, 'show']);
        Route::post('/visits/{visit}/complete', [EngineerVisitController::class, 'complete']);
        Route::post('/visits/{visit}/cancel', [EngineerVisitController::class, 'cancel']);
    });

    /*
    |----------------------------------------------------------------------
    | المزارع
    |----------------------------------------------------------------------
    */
    Route::middleware('role:farmer')->prefix('farmer')->group(function () {
        Route::get('/dashboard', [FarmerDashboardController::class, 'index']);

        Route::get('/farms', [FarmerFarmController::class, 'index']);
        Route::get('/farms/{farm}', [FarmerFarmController::class, 'show']);

        Route::get('/tasks', [FarmerTaskController::class, 'indexAll']);
        Route::get('/farms/{farm}/tasks', [FarmerTaskController::class, 'index']);
        Route::get('/tasks/{task}', [FarmerTaskController::class, 'show']);
        Route::post('/tasks/{task}/submissions', [FarmerTaskController::class, 'storeSubmission']);

        Route::get('/visits/{visit}', [FarmerVisitController::class, 'show']);
    });
});
