<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, CustomerController, BusinessUserController, StaffUserController};

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Authentication routes
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::post('register', [AuthController::class, 'register'])->name('register');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
            Route::get('me', [AuthController::class, 'me'])->name('me');
        });
    });

    Route::middleware(['auth:sanctum', \App\Http\Middleware\UnifiedAuth::class])->group(function () {

        // Customer routes
        Route::apiResource('customers', CustomerController::class)
            ->middleware('staff:read|customer:read')
            ->except(['store']);
        Route::post('customers', [CustomerController::class, 'store'])
            ->middleware('staff:admin');

        // Business User routes
        Route::apiResource('business-users', BusinessUserController::class)
            ->middleware('business:read|staff:read');
        Route::post('business-users/{business_user}/toggle-status', [BusinessUserController::class, 'toggleStatus'])
            ->middleware('business:manage|staff:admin');

        // Staff User routes
        Route::apiResource('staff-users', StaffUserController::class)
            ->middleware('staff:read');
        Route::get('staff-users/dashboard', [StaffUserController::class, 'dashboard'])
            ->middleware('staff:admin');
    });
});
