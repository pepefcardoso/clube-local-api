<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StaffUserProfileController;
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

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        Route::patch('/{user}/activate', [UserController::class, 'activate']);
        Route::patch('/{user}/deactivate', [UserController::class, 'deactivate']);
    });

    Route::middleware(['auth:sanctum', 'ensure.active.user'])
        ->prefix('staff')
        ->group(function () {
            Route::get('/', [StaffUserProfileController::class, 'index']);
            Route::post('/', [StaffUserProfileController::class, 'store']);
            Route::get('/{staffUserProfile}', [StaffUserProfileController::class, 'show']);
            Route::put('/{staffUserProfile}', [StaffUserProfileController::class, 'update']);
            Route::delete('/{staffUserProfile}', [StaffUserProfileController::class, 'destroy']);
            Route::patch('/{staffUserProfile}/permissions', [StaffUserProfileController::class, 'updatePermissions']);
            Route::patch('/{staffUserProfile}/access-level', [StaffUserProfileController::class, 'updateAccessLevel']);
        });
});
