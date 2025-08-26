<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckTokenAbility
{
    public function handle(Request $request, Closure $next, string ...$abilities)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = Auth::user()->currentAccessToken();

        if (!$token) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $hasAbility = false;
        foreach ($abilities as $ability) {
            if ($token->can($ability)) {
                $hasAbility = true;
                break;
            }
        }

        if (!$hasAbility) {
            return response()->json(['message' => 'Token lacks required abilities'], 403);
        }

        return $next($request);
    }
}
