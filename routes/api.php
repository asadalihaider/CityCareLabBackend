<?php

use App\Http\Controllers\Api\CustomerAuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('customer')->group(function () {
    Route::post('register', [CustomerAuthController::class, 'register']);
    Route::post('login', [CustomerAuthController::class, 'login']);
});

// Protected routes
Route::middleware(['auth:sanctum'])->prefix('customer')->group(function () {
    Route::get('profile', [CustomerAuthController::class, 'profile']);
    Route::post('logout', [CustomerAuthController::class, 'logout']);
    Route::post('refresh', [CustomerAuthController::class, 'refresh']);
});
