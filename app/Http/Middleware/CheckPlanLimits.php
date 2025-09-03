<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPlanLimits
{
    public function handle(Request $request, Closure $next, string $limitType)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        if (!$user->isBusinessUser()) {
            return $next($request);
        }

        $business = $user->profileable->business;

        if (!$business || !$business->hasActivePlan()) {
            return response()->json([
                'message' => 'Business does not have an active plan',
                'code' => 'NO_ACTIVE_PLAN'
            ], 403);
        }

        switch ($limitType) {
            case 'users':
                if (!$business->canAddMoreUsers()) {
                    return response()->json([
                        'message' => 'User limit reached for current plan',
                        'code' => 'USER_LIMIT_REACHED',
                        'current_limit' => $business->platformPlan->max_users,
                        'current_count' => $business->businessUserProfiles()->count()
                    ], 403);
                }
                break;

            case 'customers':
                if (!$business->canAddMoreCustomers()) {
                    return response()->json([
                        'message' => 'Customer limit reached for current plan',
                        'code' => 'CUSTOMER_LIMIT_REACHED',
                        'current_limit' => $business->platformPlan->max_customers,
                        'current_count' => $business->customers()->count()
                    ], 403);
                }
                break;
        }

        return $next($request);
    }
}
