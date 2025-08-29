<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $hasRole = false;

        foreach ($roles as $role) {
            $hasRole = match ($role) {
                'customer' => $user->isCustomer(),
                'business_user' => $user->isBusinessUser(),
                'business_manager' => $user->isBusinessUser() && $user->profileable->isManager(),
                'business_admin' => $user->isBusinessUser() && $user->profileable->isAdmin(),
                'staff_basic' => $user->isStaff() && $user->profileable->isBasic(),
                'staff_advanced' => $user->isStaff() && $user->profileable->isAdvanced(),
                'staff_admin' => $user->isStaff() && $user->profileable->isAdmin(),
                default => false,
            };

            if ($hasRole) {
                break;
            }
        }

        if (!$hasRole) {
            return response()->json(['message' => 'Insufficient permissions'], 403);
        }

        return $next($request);
    }
}
