<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnifiedAuth
{
    public function handle(Request $request, Closure $next, ...$requirements): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        foreach ($requirements as $requirement) {
            if (!$this->checkRequirement($user, $requirement)) {
                return response()->json([
                    'message' => 'Access denied',
                    'required' => $requirements,
                ], 403);
            }
        }

        return $next($request);
    }

    private function checkRequirement($user, string $requirement): bool
    {
        if (str_contains($requirement, ':')) {
            return $user->tokenCan($requirement);
        }

        if (str_contains($requirement, '.')) {
            return $user->hasRole($requirement);
        }

        $userTypes = [
            'customer' => \App\Models\Customer::class,
            'business' => \App\Models\BusinessUser::class,
            'staff' => \App\Models\StaffUser::class,
        ];

        if (isset($userTypes[$requirement])) {
            return $user instanceof $userTypes[$requirement];
        }

        return false;
    }
}
