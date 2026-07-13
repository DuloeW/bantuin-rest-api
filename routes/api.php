<?php

use App\Http\Controllers\Api\Address\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BankAccount\BankAccountController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\Message\MessageController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\Offer\OfferController;
use App\Http\Controllers\Api\Payment\PaymentController;
use App\Http\Controllers\Api\Post\PostController;
use App\Http\Controllers\Api\Skill\SkillController;
use App\Http\Controllers\Api\Transaction\TransactionController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\User\UserSkillController;
use App\Http\Controllers\Api\Withdrawal\WithdrawalController;
use App\Http\Controllers\Api\Notification\DeviceTokenController;
use App\Http\Controllers\Api\Notification\PushNotificationController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

Route::post('/notifications/test-direct', [PushNotificationController::class, 'testDirect']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class,'logout']);

    Route::get('/user/skills', [UserSkillController::class, 'index']);
    Route::post('/user/skills', [UserSkillController::class, 'store']);
    Route::delete('/user/skills/{skillId}', [UserSkillController::class, 'destroy']);

    Route::get('/users/profile', [UserController::class, 'getProfile']);
    Route::get('/users/activity-analytics', [UserController::class, 'getActivityAnalytics']);
    Route::get('/users/jobs', [UserController::class, 'getMyJobs']);
    Route::get('/users', [UserController::class, 'getAll']);
    Route::put('/users', [UserController::class, 'update']);
    Route::post('/users/ktp', [UserController::class, 'updateKtp']);
    Route::patch('/users/wallet', [UserController::class, 'updateWalletBalance']);
    Route::put('/users/password', [UserController::class, 'changePassword']);
    Route::get('/users/first-name/{name}', [UserController::class, 'getByFirstName']);
    Route::get('/users/last-name/{name}', [UserController::class, 'getByLastName']);
    Route::get('/users/{id}', [UserController::class, 'getById']);
    Route::get('/users/posts/{id}/offer/inactive', [UserController::class, 'getUserInactiveOfferPosts']);
    Route::get('/users/posts/{id}', [UserController::class, 'getUsersPosts']);
    Route::post('/users/{id}/report', [UserController::class, 'reportUser']);
    Route::patch('/users/accept-terms', [UserController::class, 'acceptTerms']);
    Route::get('/users/has-bank-account/{userId}', [UserController::class, 'hasBankAccount']);

    Route::get('/categories', [CategoryController::class, 'getAll']);
    Route::post('/categories', [CategoryController::class, 'create']);
    Route::get('/categories/slug/{slug}', [CategoryController::class, 'getBySlug']);
    Route::get('/categories/{id}', [CategoryController::class, 'getById']);
    Route::delete('/categories/{id}', [CategoryController::class, 'delete']);

    Route::get('/posts', [PostController::class, 'getAll']);
    Route::get('/posts/search', [PostController::class, 'search']);
    Route::get('/posts/near-me', [PostController::class, 'nearMe']);
    Route::get('/posts/total', [PostController::class, 'getTotalUserPosts']);
    Route::post('/posts/request', [PostController::class, 'createRequest']);
    Route::post('/posts/offer', [PostController::class, 'createOffer']);
    Route::get('/posts/request', [PostController::class, 'getAllWithRequestDetails']);
    Route::get('/posts/offer', [PostController::class, 'getAllWithOfferDetails']);
    Route::get('/posts/{id}', [PostController::class, 'getById']);
    Route::get('/posts/{id}/reviews', [PostController::class, 'getReviews']);
    Route::delete('/posts/{id}', [PostController::class, 'delete']);
    Route::post('/posts/{id}/update', [PostController::class, 'update']);
    Route::post('/posts/{id}/report', [PostController::class, 'reportPost']);

    Route::post('/posts/apply', [OfferController::class, 'applyForJob']);
    Route::post('/posts/book-helper', [OfferController::class, 'bookHelperService']);

    Route::get('/addresses/provinces', [AddressController::class, 'getProvinces']);
    Route::get('/addresses/provinces/{provinceId}/cities', [AddressController::class, 'getCitiesByProvince']);
    Route::get('/addresses/cities/{cityId}/districts', [AddressController::class, 'getDistrictsByCity']);
    Route::get('/addresses/districts/{districtId}/villages', [AddressController::class, 'getVillagesByDistrict']);
    Route::post('/addresses/match', [AddressController::class, 'matchAddressNames']);

    Route::get('/offers/post/{postId}', [OfferController::class, 'getOffersForPost']);
    Route::post('/offers/accept', [OfferController::class, 'acceptHelper']);
    Route::get('/offers/{offerId}', [OfferController::class, 'show']);
    Route::post('/offers/{offerId}/finalize', [OfferController::class, 'finalizeOffer']);

    Route::post('/offers/{offerId}/messages', [MessageController::class, 'sendMessage']);
    Route::get('/offers/{offerId}/messages', [MessageController::class, 'getMessages']);

    Route::get('/skills', [SkillController::class, 'getAllSkills']);
    Route::get('/skills/{id}', [SkillController::class, 'getSkillById']);
    Route::get('/skills/name/{name}', [SkillController::class, 'getSkillByName']);
    Route::get('/skills/search/{name}', [SkillController::class, 'searchSkillsByName']);

    // Bank Accounts (untuk helper mendaftarkan rekening penerima dana escrow)
    Route::get('/bank-accounts/supported-banks', [BankAccountController::class, 'supportedBanks']);
    Route::get('/bank-accounts', [BankAccountController::class, 'index']);
    Route::post('/bank-accounts', [BankAccountController::class, 'store']);
    Route::patch('/bank-accounts/{id}/primary', [BankAccountController::class, 'setPrimary']);
    Route::delete('/bank-accounts/{id}', [BankAccountController::class, 'destroy']);

    // Payment & Escrow
    Route::post('/payments', [PaymentController::class, 'create']);
    Route::get('/payments/transactions/{transactionId}', [PaymentController::class, 'status']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/active', [TransactionController::class, 'activeTransactions']);
    Route::get('/transactions/reviewed', [TransactionController::class, 'reviewedHistory']);
    Route::get('/transactions/{id}', [TransactionController::class, 'getById']);
    Route::post('/transactions/{id}/reviews', [TransactionController::class, 'review']);
    Route::post('/transactions/{id}/complete', [TransactionController::class, 'complete']);
    Route::post('/transactions/{id}/update', [TransactionController::class, 'update']);
    Route::post('/transactions/{id}/cancel', [TransactionController::class, 'cancel']);
    Route::post('/transactions/{id}/approve', [TransactionController::class, 'approve']);
    Route::post('/transactions/{id}/transfer', [TransactionController::class, 'transfer']);
    Route::post('/transactions/{id}/revision', [TransactionController::class, 'revision']);
    Route::post('/revisions/{revisionId}/respond', [TransactionController::class, 'respondRevision']);
    Route::post('/transactions/{id}/refund', [TransactionController::class, 'requestRefund']);
    Route::post('/refunds/{refundId}/respond', [TransactionController::class, 'respondRefund']);
    Route::get('/transactions/{id}/dispute', [TransactionController::class, 'getDisputeDetail']);

    // Withdrawals (Penarikan Dana Helper)
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);

    // Device Tokens (FCM Notifications)
    Route::post('/device-tokens/register', [DeviceTokenController::class, 'register']);
    Route::post('/device-tokens/unregister', [DeviceTokenController::class, 'unregister']);

    // In-App Notifications
    Route::get('/notifications', [NotificationController::class, 'getPending']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Push Notifications (FCM)
    Route::post('/notifications/send', [PushNotificationController::class, 'send']);
    Route::post('/notifications/send-self', [PushNotificationController::class, 'sendToSelf']);
    Route::post('/notifications/broadcast', [PushNotificationController::class, 'broadcast']);
});