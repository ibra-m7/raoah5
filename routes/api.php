<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AiAssistantController;
use App\Http\Controllers\Api\Auth\OtpAuthController;
use App\Http\Controllers\Api\BundleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\Courier\CourierAuthController;
use App\Http\Controllers\Api\Courier\CourierOrderController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\DynamicPageController;
use App\Http\Controllers\Api\SearchLogController;
use App\Http\Controllers\Api\StartupController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::get('startup', StartupController::class);
Route::get('home', HomeController::class);
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{category}', [CategoryController::class, 'show']);
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}/recommendations', [RecommendationController::class, 'forProduct']);
Route::post('recommendations/cart', [RecommendationController::class, 'forCart']);
Route::get('products/{product}', [ProductController::class, 'show']);
Route::get('pages/{page}', [DynamicPageController::class, 'show']);
Route::get('bundles/{bundle}', [BundleController::class, 'show']);
Route::post('search/log', [SearchLogController::class, 'store'])->middleware('throttle:60,1');

Route::get('ai/config', [AiAssistantController::class, 'config']);
Route::post('ai/chat', [AiAssistantController::class, 'chat'])
    ->middleware('throttle:ai');

Route::prefix('auth')->group(function () {
    Route::post('otp/request', [OtpAuthController::class, 'requestOtp'])
        ->middleware('throttle:otp');
    Route::post('otp/verify', [OtpAuthController::class, 'verifyOtp'])
        ->middleware('throttle:12,1');

    Route::middleware(['auth:sanctum', 'customer'])->group(function () {
        Route::get('me', [OtpAuthController::class, 'me']);
        Route::patch('me', [OtpAuthController::class, 'updateMe']);
        Route::post('location', [OtpAuthController::class, 'saveLocation']);
        Route::post('logout', [OtpAuthController::class, 'logout']);
        Route::patch('notifications', [NotificationController::class, 'updatePreference']);
    });
});

Route::prefix('courier')->group(function () {
    Route::post('login', [CourierAuthController::class, 'login'])->middleware('throttle:12,1');

    Route::middleware(['auth:sanctum', 'courier'])->group(function () {
        Route::get('me', [CourierAuthController::class, 'me']);
        Route::patch('availability', [CourierAuthController::class, 'availability']);
        Route::post('logout', [CourierAuthController::class, 'logout']);
        Route::get('orders/current', [CourierOrderController::class, 'current']);
        Route::get('orders/previous', [CourierOrderController::class, 'previous']);
        Route::get('orders/{order}', [CourierOrderController::class, 'show']);
        Route::post('orders/{order}/accept', [CourierOrderController::class, 'accept']);
        Route::post('orders/{order}/pickup', [CourierOrderController::class, 'pickup']);
        Route::post('orders/{order}/deliver', [CourierOrderController::class, 'deliver']);
        Route::get('account', [CourierAuthController::class, 'account']);
    });
});

Route::middleware(['auth:sanctum', 'customer'])->group(function () {
    Route::get('addresses', [AddressController::class, 'index']);
    Route::post('addresses', [AddressController::class, 'store']);
    Route::patch('addresses/{address}', [AddressController::class, 'update']);
    Route::delete('addresses/{address}', [AddressController::class, 'destroy']);

    Route::post('coupons/preview', [CouponController::class, 'preview']);
    Route::post('delivery/quote', [DeliveryController::class, 'quote'])->name('delivery.quote');
    Route::get('delivery/slots', [DeliveryController::class, 'slots']);
    Route::get('pickup/slots', [\App\Http\Controllers\Api\PickupController::class, 'slots']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('device-tokens', [NotificationController::class, 'registerToken']);
    Route::delete('device-tokens', [NotificationController::class, 'unregisterToken']);
});
