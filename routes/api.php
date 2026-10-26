<?php

declare(strict_types=1);

use App\Http\Controllers\CreateCouponController;
use App\Http\Controllers\CreateQuoteController;
use App\Http\Controllers\DeactivateCouponController;
use App\Http\Controllers\ListCouponRedemptionsController;
use App\Http\Controllers\ListCouponsController;
use App\Http\Controllers\ListPricesController;
use App\Http\Controllers\SetPriceController;
use Illuminate\Support\Facades\Route;

// Called only by the BFFs (and checkout from P3). Contract: shopwise-contracts/openapi/pricing.yaml.
// Prices and quotes are public: they are what the storefront shows anyway.
Route::get('/prices', ListPricesController::class)->name('prices.index');
// A quote with the customer's token also checks the coupon's per-customer limit (checkout always sends it).
Route::post('/quotes', CreateQuoteController::class)->middleware('auth.jwt.optional')->name('quotes.store');

Route::middleware('auth.jwt:admin')->prefix('admin')->name('admin.')->group(function (): void {
    Route::put('/prices/{sku}', SetPriceController::class)->name('prices.update');
    Route::get('/coupons', ListCouponsController::class)->name('coupons.index');
    Route::post('/coupons', CreateCouponController::class)->name('coupons.store');
    Route::delete('/coupons/{code}', DeactivateCouponController::class)->name('coupons.destroy');
    Route::get('/coupons/{code}/redemptions', ListCouponRedemptionsController::class)->name('coupons.redemptions.index');
});
