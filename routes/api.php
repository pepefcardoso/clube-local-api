<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController, CustomerController, BusinessUserController, StaffUserController};

Route::prefix('v1')->name('api.v1.')->group(function () {
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

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('customers', CustomerController::class);

        Route::apiResource('business-users', BusinessUserController::class);

        Route::apiResource('staff-users', StaffUserController::class);

        Route::get('dashboard', [StaffUserController::class, 'dashboard'])
            ->middleware('unified.auth:staff.admin');
    });
});
