<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BusinessUserController;
use App\Http\Controllers\StaffUserController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::prefix('customers')->group(function () {
    Route::post('/register', [CustomerController::class, 'register']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])
            ->middleware('ability:staff:read');

        Route::post('/', [CustomerController::class, 'store'])
            ->middleware('ability:staff:admin');

        Route::get('/{customer}', [CustomerController::class, 'show'])
            ->middleware('ability:customer:read,staff:read');

        Route::put('/{customer}', [CustomerController::class, 'update'])
            ->middleware('ability:customer:update,staff:admin');

        Route::delete('/{customer}', [CustomerController::class, 'destroy'])
            ->middleware('ability:staff:admin');
    });
});

Route::prefix('business-users')->group(function () {
    Route::post('/register', [BusinessUserController::class, 'register']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [BusinessUserController::class, 'index'])
            ->middleware('ability:business:read,business:manage,staff:read');

        Route::post('/', [BusinessUserController::class, 'store'])
            ->middleware('ability:business:manage,staff:admin');

        Route::get('/{business_user}', [BusinessUserController::class, 'show'])
            ->middleware('ability:business:read,business:manage,staff:read');

        Route::put('/{business_user}', [BusinessUserController::class, 'update'])
            ->middleware('ability:business:update,business:manage,staff:admin');

        Route::delete('/{business_user}', [BusinessUserController::class, 'destroy'])
            ->middleware('ability:business:manage,staff:admin');

        Route::post('/{business_user}/activate', [BusinessUserController::class, 'activate'])
            ->middleware('ability:business:manage,staff:admin');

        Route::post('/{business_user}/deactivate', [BusinessUserController::class, 'deactivate'])
            ->middleware('ability:business:manage,staff:admin');

        Route::get('/company/users', [BusinessUserController::class, 'byCompany'])
            ->middleware('ability:business:manage,staff:read');
    });
});

Route::prefix('staff-users')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [StaffUserController::class, 'index'])
            ->middleware('ability:staff:read');

        Route::post('/', [StaffUserController::class, 'store'])
            ->middleware('ability:staff:admin');

        Route::get('/{staff_user}', [StaffUserController::class, 'show'])
            ->middleware('ability:staff:read');

        Route::put('/{staff_user}', [StaffUserController::class, 'update'])
            ->middleware('ability:staff:update,staff:admin');

        Route::delete('/{staff_user}', [StaffUserController::class, 'destroy'])
            ->middleware('ability:staff:admin');

        Route::post('/{staff_user}/activate', [StaffUserController::class, 'activate'])
            ->middleware('ability:staff:admin');

        Route::post('/{staff_user}/deactivate', [StaffUserController::class, 'deactivate'])
            ->middleware('ability:staff:admin');

        Route::get('/dashboard', [StaffUserController::class, 'dashboard'])
            ->middleware('ability:staff:admin,system:manage');
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
    ]);
});
