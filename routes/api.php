<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessUserProfileController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\PlatformPlanController;
use App\Http\Controllers\StaffUserProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rota de teste simples para o usuário logado
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas de autenticação, acessíveis publicamente
Route::post('/auth/login', [AuthController::class, 'login']);

// Agrupa todas as rotas que requerem autenticação e um usuário ativo
Route::middleware(['auth:sanctum', 'ensure.active.user'])->group(function () {

    // Rotas de autenticação (privadas)
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Rota de perfil do usuário
    Route::get('/profile', [UserController::class, 'profile']);

    // Rotas para usuários (incluindo ativação/desativação)
    Route::apiResource('users', UserController::class)->except(['create', 'edit']);
    Route::patch('/users/{user}/activate', [UserController::class, 'activate']);
    Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate']);

    // Rotas para endereços (incluindo definição de endereço primário)
    Route::apiResource('addresses', AddressController::class)->except(['create', 'edit']);
    Route::patch('/addresses/{address}/set-primary', [AddressController::class, 'setPrimary']);

    // Rotas aninhadas para endereços de negócios e clientes
    Route::prefix('businesses/{business}')->group(function () {
        Route::get('/addresses', [AddressController::class, 'getBusinessAddresses']);
        Route::post('/addresses', [AddressController::class, 'storeBusinessAddress']);
    });

    Route::prefix('customers/{customer}')->group(function () {
        Route::get('/addresses', [AddressController::class, 'getCustomerAddresses']);
        Route::post('/addresses', [AddressController::class, 'storeCustomerAddress']);
    });

    // Rotas para perfis de funcionários (staff)
    Route::apiResource('staff', StaffUserProfileController::class)->except(['create', 'edit']);
    Route::patch('/staff/{staffUserProfile}/access-level', [StaffUserProfileController::class, 'updateAccessLevel']);

    // Rotas para negócios
    Route::apiResource('businesses', BusinessController::class)->except(['create', 'edit']);
    Route::patch('/businesses/{business}/approve', [BusinessController::class, 'approve']);
    Route::patch('/businesses/{business}/suspend', [BusinessController::class, 'suspend']);
    Route::patch('/businesses/{business}/activate', [BusinessController::class, 'activate']);
    Route::patch('/businesses/{business}/deactivate', [BusinessController::class, 'deactivate']);
    Route::patch('/businesses/{business}/assign-plan', [BusinessController::class, 'assignPlan']);
    Route::delete('/businesses/{business}/remove-plan', [BusinessController::class, 'removePlan']);
    Route::get('/businesses/{business}/stats', [BusinessController::class, 'getStats']);

    // Rotas para perfis de usuários de negócios e clientes (com middleware de acesso)
    Route::middleware(['check.business.access'])
        ->prefix('businesses/{business_id}')
        ->group(function () {
            Route::apiResource('users', BusinessUserProfileController::class)->except(['create', 'edit']);
            Route::apiResource('customers', CustomerProfileController::class)->except(['create', 'edit']);
        });

    // Rotas para planos da plataforma
    Route::apiResource('platform-plans', PlatformPlanController::class)->except(['create', 'edit']);
    Route::patch('/platform-plans/{platformPlan}/activate', [PlatformPlanController::class, 'activate']);
    Route::patch('/platform-plans/{platformPlan}/deactivate', [PlatformPlanController::class, 'deactivate']);
    Route::patch('/platform-plans/{platformPlan}/toggle-featured', [PlatformPlanController::class, 'toggleFeatured']);

    // Rotas para perfis de clientes
    Route::apiResource('customers', CustomerProfileController::class)->except(['create', 'edit']);
});
