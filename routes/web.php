<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Barcode\BarcodeGeneratorPageController;
use App\Http\Controllers\Barcode\BarcodePreviewController;
use App\Http\Controllers\Barcode\BarcodeValidationController;
use App\Http\Controllers\Barcode\BarcodeTypeConfigController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\PricingController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/pricing', PricingController::class)->name('pricing');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('/app/barcodes/generator', BarcodeGeneratorPageController::class)
        ->name('app.barcodes.generator');
    Route::get('/app/barcodes/types/{barcodeType:slug}/config', BarcodeTypeConfigController::class)
        ->name('app.barcodes.types.config');
    Route::post('/app/barcodes/preview', BarcodePreviewController::class)
        ->name('app.barcodes.preview');
    Route::post('/app/barcodes/validate', BarcodeValidationController::class)
        ->name('app.barcodes.validate');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
