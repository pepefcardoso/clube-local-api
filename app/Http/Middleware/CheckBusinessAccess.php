<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBusinessAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $businessId = $request->route('businessId') ?? $request->route('business_id') ?? $request->input('business_id');

        if (!$businessId) {
            return response()->json(['message' => 'Business ID is required'], 400);
        }

        if ($user->isStaff() && $user->profileable->isAdmin()) {
            return $next($request);
        }

        if ($user->isBusinessUser()) {
            $businessProfile = $user->profileable;

            if (
                $businessProfile &&
                $businessProfile->business_id == $businessId &&
                $businessProfile->isActive()
            ) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Access denied to this business'], 403);
    }
}
