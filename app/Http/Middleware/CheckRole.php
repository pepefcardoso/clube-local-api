<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'Access denied. Insufficient permissions.',
                'required_roles' => $roles,
                'user_roles' => $user->getRoleNames()->toArray(),
            ], 403);
        }

        return $next($request);
    }
}