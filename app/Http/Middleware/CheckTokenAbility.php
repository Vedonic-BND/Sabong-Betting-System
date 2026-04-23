<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = $user->currentAccessToken();

        // admin token can access all routes
        if ($token->name === 'admin') {
            return $next($request);
        }

        // check if token has the required ability
        if (!$token->can($ability)) {
            return response()->json(['message' => 'Access denied for this app.'], 403);
        }

        return $next($request);
    }
}
