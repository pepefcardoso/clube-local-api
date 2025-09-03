<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StaffUserProfileController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessUserProfileController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\PlatformPlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'ensure.active.user'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::get('/profile', [UserController::class, 'profile']);

    // User routes
    Route::apiResource('users', UserController::class)->except(['create', 'edit']);
    Route::patch('/users/{user}/activate', [UserController::class, 'activate']);
    Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate']);

    // Address routes
    Route::apiResource('addresses', AddressController::class)->except(['create', 'edit']);
    Route::patch('/addresses/{address}/set-primary', [AddressController::class, 'setPrimary']);

    // Business-specific address routes
    Route::prefix('businesses/{business}')->group(function () {
        Route::get('/addresses', [AddressController::class, 'getBusinessAddresses']);
        Route::post('/addresses', [AddressController::class, 'storeBusinessAddress']);
    });

    // Customer-specific address routes
    Route::prefix('customers/{customer}')->group(function () {
        Route::get('/addresses', [AddressController::class, 'getCustomerAddresses']);
        Route::post('/addresses', [AddressController::class, 'storeCustomerAddress']);
    });

    // Staff routes
    Route::apiResource('staff', StaffUserProfileController::class)->except(['create', 'edit']);
    Route::patch('/staff/{staffUserProfile}/access-level', [StaffUserProfileController::class, 'updateAccessLevel']);

    // Business routes
    Route::apiResource('businesses', BusinessController::class)->except(['create', 'edit']);

    // Business-scoped routes
    Route::middleware(['check.business.access'])
        ->prefix('businesses/{business_id}')
        ->group(function () {
            Route::apiResource('users', BusinessUserProfileController::class)->except(['create', 'edit']);
            Route::apiResource('customers', CustomerProfileController::class)->except(['create', 'edit']);
        });

    // Platform plan routes
    Route::apiResource('platform-plans', PlatformPlanController::class)->except(['create', 'edit']);
    Route::patch('/platform-plans/{platformPlan}/activate', [PlatformPlanController::class, 'activate']);
    Route::patch('/platform-plans/{platformPlan}/deactivate', [PlatformPlanController::class, 'deactivate']);
    Route::patch('/platform-plans/{platformPlan}/toggle-featured', [PlatformPlanController::class, 'toggleFeatured']);

    // Customer profile routes
    Route::apiResource('customers', CustomerProfileController::class)->except(['create', 'edit']);
});
