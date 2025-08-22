<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\BusinessUserController;
use App\Http\Controllers\StaffUserController;

Route::name('api.')->group(function () {
    Route::prefix('v1')->name('v1.')->group(function () {

        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('login', [AuthController::class, 'login'])->name('login');

            Route::middleware('auth:sanctum')->group(function () {
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
                Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
                Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
                Route::get('me', [AuthController::class, 'me'])->name('me');
            });
        });

        Route::prefix('customers')->name('customers.')->group(function () {
            Route::post('register', [CustomerController::class, 'register'])->name('register');

            Route::middleware('auth:sanctum')->group(function () {
                Route::get('/', [CustomerController::class, 'index'])
                    ->middleware('ability:staff:read')
                    ->name('index');

                Route::post('/', [CustomerController::class, 'store'])
                    ->middleware('ability:staff:admin')
                    ->name('store');

                Route::get('{customer}', [CustomerController::class, 'show'])
                    ->middleware('ability:customer:read,staff:read')
                    ->name('show');

                Route::put('{customer}', [CustomerController::class, 'update'])
                    ->middleware('ability:customer:update,staff:admin')
                    ->name('update');

                Route::delete('{customer}', [CustomerController::class, 'destroy'])
                    ->middleware('ability:staff:admin')
                    ->name('destroy');
            });
        });

        Route::prefix('business-users')->name('business-users.')->group(function () {
            Route::post('register', [BusinessUserController::class, 'register'])->name('register');

            Route::middleware('auth:sanctum')->group(function () {
                Route::get('/', [BusinessUserController::class, 'index'])
                    ->middleware('ability:business:read,business:manage,staff:read')
                    ->name('index');

                Route::post('/', [BusinessUserController::class, 'store'])
                    ->middleware('ability:business:manage,staff:admin')
                    ->name('store');

                Route::get('{business_user}', [BusinessUserController::class, 'show'])
                    ->middleware('ability:business:read,business:manage,staff:read')
                    ->name('show');

                Route::put('{business_user}', [BusinessUserController::class, 'update'])
                    ->middleware('ability:business:update,business:manage,staff:admin')
                    ->name('update');

                Route::delete('{business_user}', [BusinessUserController::class, 'destroy'])
                    ->middleware('ability:business:manage,staff:admin')
                    ->name('destroy');

                Route::post('{business_user}/activate', [BusinessUserController::class, 'activate'])
                    ->middleware('ability:business:manage,staff:admin')
                    ->name('activate');

                Route::post('{business_user}/deactivate', [BusinessUserController::class, 'deactivate'])
                    ->middleware('ability:business:manage,staff:admin')
                    ->name('deactivate');

                Route::get('company/users', [BusinessUserController::class, 'byCompany'])
                    ->middleware('ability:business:manage,staff:read')
                    ->name('by-company');
            });
        });

        Route::prefix('staff-users')->name('staff-users.')->middleware('auth:sanctum')->group(function () {
            Route::get('/', [StaffUserController::class, 'index'])
                ->middleware('ability:staff:read')
                ->name('index');

            Route::post('/', [StaffUserController::class, 'store'])
                ->middleware('ability:staff:admin')
                ->name('store');

            Route::get('{staff_user}', [StaffUserController::class, 'show'])
                ->middleware('ability:staff:read')
                ->name('show');

            Route::put('{staff_user}', [StaffUserController::class, 'update'])
                ->middleware('ability:staff:update,staff:admin')
                ->name('update');

            Route::delete('{staff_user}', [StaffUserController::class, 'destroy'])
                ->middleware('ability:staff:admin')
                ->name('destroy');

            Route::post('{staff_user}/activate', [StaffUserController::class, 'activate'])
                ->middleware('ability:staff:admin')
                ->name('activate');

            Route::post('{staff_user}/deactivate', [StaffUserController::class, 'deactivate'])
                ->middleware('ability:staff:admin')
                ->name('deactivate');

            Route::get('dashboard', [StaffUserController::class, 'dashboard'])
                ->middleware('ability:staff:admin,system:manage')
                ->name('dashboard');
        });

        Route::middleware('auth:sanctum')->get('user', function (Request $request) {
            return $request->user();
        })->name('user');
    });
});

Route::get('health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'environment' => app()->environment(),
        'version' => '1.0.0',
    ]);
})->name('health');
