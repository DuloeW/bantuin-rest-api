<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Notification\DeviceTokenController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/users/profile', [UserController::class, 'getProfile']);

    Route::get('/users', [UserController::class, 'getAll']);
    Route::get('/users/{id}', [UserController::class, 'getById']);
    Route::get('/users/first-name/{name}', [UserController::class, 'getByFirstName']);
    Route::get('/users/last-name/{name}', [UserController::class, 'getByLastName']);
    Route::put('/users', [UserController::class, 'update']);

   Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/notifications/device-token', [DeviceTokenController::class, 'register']);
    Route::delete('/notifications/device-token', [DeviceTokenController::class, 'unregister']);

    Route::get('/notifications', [NotificationController::class, 'getPending']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    });
});

    