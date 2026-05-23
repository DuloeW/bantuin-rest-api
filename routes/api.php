<?php

use App\Http\Controllers\Api\Address\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\Message\MessageController;
use App\Http\Controllers\Api\Offer\OfferController;
use App\Http\Controllers\Api\Post\PostController;
use App\Http\Controllers\Api\Skill\SkillController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/users/profile', [UserController::class, 'getProfile']);
    
    Route::get('/users', [UserController::class, 'getAll']);
    Route::put('/users', [UserController::class, 'update']);
    Route::get('/users/first-name/{name}', [UserController::class, 'getByFirstName']);
    Route::get('/users/last-name/{name}', [UserController::class, 'getByLastName']);
    Route::get('/users/{id}', [UserController::class, 'getById']);
    Route::get('/users/posts/{id}', [UserController::class, 'getUsersPosts']);

    Route::get('/categories', [CategoryController::class, 'getAll']);
    Route::post('/categories', [CategoryController::class, 'create']);
    Route::get('/categories/slug/{slug}', [CategoryController::class, 'getBySlug']);
    Route::get('/categories/{id}', [CategoryController::class, 'getById']);
    Route::delete('/categories/{id}', [CategoryController::class, 'delete']);

    Route::get('/posts', [PostController::class, 'getAll']);
    Route::delete('/posts/{id}', [PostController::class, 'delete']);
    Route::get('/posts/total', [PostController::class, 'getTotalUserPosts']);
    Route::post('/posts/request', [PostController::class, 'createRequest']);
    Route::post('/posts/offer', [PostController::class, 'createOffer']);
    Route::get('/posts/request', [PostController::class, 'getAllWithRequestDetails']);
    Route::get('/posts/offer', [PostController::class, 'getAllWithOfferDetails']);
    Route::delete('/posts/{id}', [PostController::class, 'delete']);

    Route::get('addresses/provinces', [AddressController::class, 'getProvinces']);
    Route::get('addresses/provinces/{provinceId}/cities', [AddressController::class, 'getCitiesByProvince']);
    Route::get('addresses/cities/{cityId}/districts', [AddressController::class, 'getDistrictsByCity']);
    Route::get('addresses/districts/{districtId}/villages', [AddressController::class, 'getVillagesByDistrict']);

    Route::post('/posts/apply', [OfferController::class, 'applyForJob']);
    Route::post('/posts/book-helper', [OfferController::class, 'bookHelperService']);

    Route::get('/offers/post/{postId}', [OfferController::class, 'getOffersForPost']);

    Route::post('/offers/{offerId}/messages', [MessageController::class, 'sendMessage']);

    Route::get('/skills', [SkillController::class, 'getAllSkills']);
    Route::get('/skills/{id}', [SkillController::class, 'getSkillById']);
    Route::get('/skills/name/{name}', [SkillController::class, 'getSkillByName']);
    Route::get('/skills/search/{name}', [SkillController::class, 'searchSkillsByName']);
});
    