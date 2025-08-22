<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$types): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $userType = match (true) {
            $user instanceof \App\Models\Customer => 'customer',
            $user instanceof \App\Models\BusinessUser => 'business',
            $user instanceof \App\Models\StaffUser => 'staff',
            default => null,
        };

        if (!in_array($userType, $types)) {
            return response()->json([
                'message' => 'Access denied. Invalid user type.',
                'required_types' => $types,
                'current_type' => $userType,
            ], 403);
        }

        return $next($request);
    }
}
