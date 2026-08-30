<?php

use App\Http\Controllers\Admin\AdminLiveController;
use App\Http\Controllers\Admin\AiAssistantController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CourierController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\DisplaySectionController;
use App\Http\Controllers\Admin\DynamicPageController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OfferController;
use App\Http\Controllers\Admin\OnboardingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SearchPlaceholderController;
use App\Http\Controllers\Admin\SearchSmartSuggestionController;
use App\Http\Controllers\Admin\SearchTrendingPinController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SplashScreenController;
use App\Http\Controllers\Admin\StorePaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('live', [AdminLiveController::class, 'index'])->name('live');
    Route::post('live/read', [AdminLiveController::class, 'markRead'])->name('live.read');
    Route::get('search', SearchController::class)->name('search');

    Route::get('products/lookup', [ProductController::class, 'lookup'])->name('products.lookup');
    Route::post('products/generate-copy', [ProductController::class, 'generateCopy'])
        ->middleware('throttle:admin-ai-copy')
        ->name('products.generate-copy');
    Route::post('products/generate-all-copy', [ProductController::class, 'generateAllContent'])->name('products.generate-all-copy');
    Route::get('products/copy-generation-status', [ProductController::class, 'copyGenerationStatus'])->name('products.copy-generation-status');
    Route::post('products/cancel-copy-generation', [ProductController::class, 'cancelContentGeneration'])->name('products.cancel-copy-generation');
    Route::post('products/gift-quick', [ProductController::class, 'storeGiftQuick'])->name('products.gift-quick');
    Route::get('products/import', [ProductController::class, 'importForm'])->name('products.import');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import.store');
    Route::get('products/import/template', [ProductController::class, 'template'])->name('products.import.template');
    Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::get('offers/available', [OfferController::class, 'available'])->name('offers.available');
    Route::resource('offers', OfferController::class)->except(['show'])->parameters(['offers' => 'product']);
    Route::resource('banners', BannerController::class)->except(['show']);
    Route::resource('dynamic-pages', DynamicPageController::class)->except(['show']);
    Route::resource('home-sections', HomeSectionController::class)->except(['show']);
    Route::resource('display-sections', DisplaySectionController::class)->except(['show']);
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('orders/{order}/items', [OrderController::class, 'updateItems'])->name('orders.items.update');
    Route::patch('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::patch('orders/{order}/courier', [OrderController::class, 'updateCourier'])->name('orders.courier');
    Route::resource('couriers', CourierController::class)->except(['show']);
    Route::post('couriers/{courier}/settle', [CourierController::class, 'settle'])->name('couriers.settle');
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('pages', [PageController::class, 'index'])->name('pages.index');
    Route::resource('splash-screens', SplashScreenController::class)->except(['show']);
    Route::resource('onboarding', OnboardingController::class)->except(['show']);
    Route::resource('search-placeholders', SearchPlaceholderController::class)->except(['show', 'create', 'edit']);
    Route::post('search-smart-suggestions', [SearchSmartSuggestionController::class, 'store'])->name('search-smart-suggestions.store');
    Route::put('search-smart-suggestions/{search_smart_suggestion}', [SearchSmartSuggestionController::class, 'update'])->name('search-smart-suggestions.update');
    Route::delete('search-smart-suggestions/{search_smart_suggestion}', [SearchSmartSuggestionController::class, 'destroy'])->name('search-smart-suggestions.destroy');
    Route::post('search-trending-pins', [SearchTrendingPinController::class, 'store'])->name('search-trending-pins.store');
    Route::put('search-trending-pins/{search_trending_pin}', [SearchTrendingPinController::class, 'update'])->name('search-trending-pins.update');
    Route::delete('search-trending-pins/{search_trending_pin}', [SearchTrendingPinController::class, 'destroy'])->name('search-trending-pins.destroy');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [NotificationController::class, 'store'])->name('notifications.store');
    Route::delete('notifications/log', [NotificationController::class, 'clearLog'])->name('notifications.log.clear');
    Route::post('notifications/{campaign}/resend', [NotificationController::class, 'resend'])->name('notifications.resend');

    Route::resource('payment-methods', StorePaymentMethodController::class)
        ->parameters(['payment-methods' => 'payment_method']);

    Route::get('delivery', [DeliveryController::class, 'index'])->name('delivery.index');
    Route::put('delivery/settings', [DeliveryController::class, 'updateSettings'])->name('delivery.settings.update');
    Route::get('delivery/rules/create', [DeliveryController::class, 'create'])->name('delivery.rules.create');
    Route::post('delivery/rules', [DeliveryController::class, 'store'])->name('delivery.rules.store');
    Route::get('delivery/rules/{delivery_rule}/edit', [DeliveryController::class, 'edit'])->name('delivery.rules.edit');
    Route::put('delivery/rules/{delivery_rule}', [DeliveryController::class, 'update'])->name('delivery.rules.update');
    Route::delete('delivery/rules/{delivery_rule}', [DeliveryController::class, 'destroy'])->name('delivery.rules.destroy');
    Route::get('delivery/perks/create', [DeliveryController::class, 'createPerk'])->name('delivery.perks.create');
    Route::post('delivery/perks', [DeliveryController::class, 'storePerk'])->name('delivery.perks.store');
    Route::get('delivery/perks/{delivery_perk}/edit', [DeliveryController::class, 'editPerk'])->name('delivery.perks.edit');
    Route::put('delivery/perks/{delivery_perk}', [DeliveryController::class, 'updatePerk'])->name('delivery.perks.update');
    Route::delete('delivery/perks/{delivery_perk}', [DeliveryController::class, 'destroyPerk'])->name('delivery.perks.destroy');
    Route::post('delivery/slots', [DeliveryController::class, 'storeSlot'])->name('delivery.slots.store');
    Route::put('delivery/slots/{delivery_slot_window}', [DeliveryController::class, 'updateSlot'])->name('delivery.slots.update');
    Route::delete('delivery/slots/{delivery_slot_window}', [DeliveryController::class, 'destroySlot'])->name('delivery.slots.destroy');

    Route::resource('coupons', CouponController::class)->except(['show']);

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::delete('settings/products', [SettingController::class, 'destroyAllProducts'])->name('settings.products.destroy-all');

    Route::get('ai', [AiAssistantController::class, 'index'])->name('ai.index');
    Route::put('ai', [AiAssistantController::class, 'update'])->name('ai.update');
    Route::get('ai/conversations', [AiAssistantController::class, 'conversations'])->name('ai.conversations');
    Route::get('ai/conversations/{conversation}', [AiAssistantController::class, 'show'])->name('ai.conversations.show');
});
