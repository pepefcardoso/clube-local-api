<?php

use App\Http\Controllers\Api\AuthController;
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

    Route::middleware(['check.user.role:staff_admin'])->prefix('staff')->group(function () {
        Route::get('/', [StaffUserProfileController::class, 'index']);
        Route::post('/', [StaffUserProfileController::class, 'store']);
        Route::get('/{staffUserProfile}', [StaffUserProfileController::class, 'show']);
        Route::put('/{staffUserProfile}', [StaffUserProfileController::class, 'update']);
        Route::delete('/{staffUserProfile}', [StaffUserProfileController::class, 'destroy']);
        Route::patch('/{staffUserProfile}/permissions', [StaffUserProfileController::class, 'updatePermissions']);
        Route::patch('/{staffUserProfile}/access-level', [StaffUserProfileController::class, 'updateAccessLevel']);
    });

    Route::middleware(['check.user.role:staff_basic,staff_advanced,staff_admin'])->prefix('staff/profile')->group(function () {
        Route::get('/', function (Request $request) {
            $user = $request->user();
            if ($user->isStaff()) {
                return new \App\Http\Resources\StaffUserProfileResource($user->profileable->load('user'));
            }
            return response()->json(['message' => 'Not authorized'], 403);
        });
    });

    Route::middleware(['check.token.ability:admin:system:manage'])->prefix('admin')->group(function () {
        Route::get('/stats', function () {
            return response()->json([
                'users_count' => \App\Models\User::count(),
                'active_users_count' => \App\Models\User::where('is_active', true)->count(),
                'customers_count' => \App\Models\CustomerProfile::count(),
                'businesses_count' => \App\Models\Business::count(),
                'staff_count' => \App\Models\StaffUserProfile::count(),
            ]);
        });

        Route::prefix('users')->group(function () {
            Route::get('/inactive', function () {
                $users = \App\Models\User::where('is_active', false)->with('profileable')->paginate(15);
                return \App\Http\Resources\UserResource::collection($users);
            });

            Route::get('/by-type/{type}', function ($type) {
                $profileClass = match($type) {
                    'customer' => \App\Models\CustomerProfile::class,
                    'business' => \App\Models\BusinessUserProfile::class,
                    'staff' => \App\Models\StaffUserProfile::class,
                    default => null
                };

                if (!$profileClass) {
                    return response()->json(['message' => 'Invalid user type'], 400);
                }

                $users = \App\Models\User::where('profileable_type', $profileClass)
                    ->with('profileable')
                    ->paginate(15);

                return \App\Http\Resources\UserResource::collection($users);
            });
        });
    });

    Route::middleware(['check.business.access'])->prefix('businesses/{business_id}')->group(function () {
        Route::prefix('users')->group(function () {
            Route::get('/', function ($businessId) {
                $users = \App\Models\User::whereHas('profileable', function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })->with('profileable')->paginate(15);

                return \App\Http\Resources\UserResource::collection($users);
            });
        });

        Route::prefix('customers')->group(function () {
            Route::get('/', function ($businessId) {
                $business = \App\Models\Business::findOrFail($businessId);
                $customers = $business->customers()->with('user')->paginate(15);

                return \App\Http\Resources\CustomerProfileResource::collection($customers);
            });
        });
    });
});
