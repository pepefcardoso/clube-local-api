<?php

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

    Route::apiResource('users', UserController::class)->except(['create', 'edit']);
    Route::patch('/users/{user}/activate', [UserController::class, 'activate']);
    Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate']);

    Route::apiResource('staff', StaffUserProfileController::class)->except(['create', 'edit']);
    Route::patch('/staff/{staffUserProfile}/access-level', [StaffUserProfileController::class, 'updateAccessLevel']);

    Route::apiResource('businesses', BusinessController::class)->except(['create', 'edit']);

    Route::middleware(['check.business.access'])
        ->prefix('businesses/{business_id}')
        ->group(function () {
            Route::apiResource('users', BusinessUserProfileController::class)->except(['create', 'edit']);
            Route::apiResource('customers', CustomerProfileController::class)->except(['create', 'edit']);
        });

    Route::apiResource('platform-plans', PlatformPlanController::class)->except(['create', 'edit']);
    Route::patch('/platform-plans/{platformPlan}/activate', [PlatformPlanController::class, 'activate']);
    Route::patch('/platform-plans/{platformPlan}/deactivate', [PlatformPlanController::class, 'deactivate']);
    Route::patch('/platform-plans/{platformPlan}/toggle-featured', [PlatformPlanController::class, 'toggleFeatured']);

    Route::apiResource('customers', CustomerProfileController::class)->except(['create', 'edit']);
});
