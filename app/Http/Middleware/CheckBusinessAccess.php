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

        $hasAccess = $user->businessUserProfiles()
            ->where('business_id', $businessId)
            ->where('status', 'active')
            ->exists();

        if (!$hasAccess && !$user->hasRole('staff_admin')) {
            return response()->json(['message' => 'Access denied to this business'], 403);
        }

        return $next($request);
    }
}
