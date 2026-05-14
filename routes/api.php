<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Category\CategoryController;
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

    Route::get('/categories', [CategoryController::class, 'getAll']);
    Route::post('/categories', [CategoryController::class, 'create']);
    Route::get('/categories/{id}', [CategoryController::class, 'getById']);
    Route::delete('/categories/{id}', [CategoryController::class, 'delete']);
    Route::get('/categories/slug/{slug}', [CategoryController::class, 'getBySlug']);
});
    