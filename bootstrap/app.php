<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'unified.auth' => \App\Http\Middleware\UnifiedAuth::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. You do not have the required permissions.',
                    'error' => $e->getMessage(),
                ], 403);
            }
        });

        $exceptions->render(function (\Laravel\Sanctum\Exceptions\MissingAbilityException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. Missing required abilities.',
                    'error' => $e->getMessage(),
                    'required_abilities' => $e->abilities(),
                ], 403);
            }
        });
    })->create();
