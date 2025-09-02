<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\DiscountCardController;
use App\Http\Controllers\Api\LabCenterController;
use App\Http\Controllers\Api\LabOfferController;
use App\Http\Controllers\Api\OperatingCityController;
use App\Http\Controllers\Api\TestCategoryController;
use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('customer')->group(function () {
    Route::post('register', [CustomerAuthController::class, 'register']);
    Route::post('login', [CustomerAuthController::class, 'login']);
    Route::post('verify-otp', [CustomerAuthController::class, 'verifyOtp']);
    Route::post('resend-otp', [CustomerAuthController::class, 'resendOtp']);
    Route::post('forgot-password', [CustomerAuthController::class, 'forgotPassword']);
    Route::post('reset-password', [CustomerAuthController::class, 'resetPassword']);
});

Route::get('discount-cards', [DiscountCardController::class, 'index']);
Route::get('lab-offers', [LabOfferController::class, 'index']);
Route::get('operating-cities', [OperatingCityController::class, 'index']);
Route::get('lab-centers', [LabCenterController::class, 'index']);
Route::get('test-categories', [TestCategoryController::class, 'index']);
Route::get('tests', [TestController::class, 'index']);

// Protected routes
Route::middleware(['auth:sanctum'])->prefix('customer')->group(function () {
    Route::get('profile', [CustomerAuthController::class, 'profile']);
    Route::post('profile', [CustomerAuthController::class, 'updateProfile']);
    Route::post('logout', [CustomerAuthController::class, 'logout']);
    Route::post('refresh', [CustomerAuthController::class, 'refresh']);

    Route::apiResource('bookings', BookingController::class)->except(['destroy']);
});
