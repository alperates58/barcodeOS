<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Barcode\BarcodeTypeConfigController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\PricingController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/pricing', PricingController::class)->name('pricing');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('/app/barcodes/types/{barcodeType:slug}/config', BarcodeTypeConfigController::class)
        ->name('app.barcodes.types.config');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
