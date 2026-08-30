<?php

use App\Http\Controllers\FallbackProductImageController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\PublicStorageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketingController::class, 'index'])->name('home');

Route::get('/media/fallback-product', FallbackProductImageController::class)
    ->name('media.fallback-product');

Route::get('/storage/{path}', PublicStorageController::class)
    ->where('path', '.*')
    ->name('storage.public');
