<?php

use App\Http\Controllers\FallbackProductImageController;
use App\Http\Controllers\MarketingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', [MarketingController::class, 'index'])->name('home');

Route::get('/media/fallback-product', FallbackProductImageController::class)
    ->name('media.fallback-product');

Route::get('/storage/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('storage.public');
