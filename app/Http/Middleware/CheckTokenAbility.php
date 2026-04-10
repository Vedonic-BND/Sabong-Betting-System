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

        // admin token can access both admin AND cashin routes
        if ($token->name === 'admin') {
            return $next($request);
        }

        // teller tokens only access their specific route
        if ($token->name !== $ability) {
            return response()->json(['message' => 'Access denied for this app.'], 403);
        }

        return $next($request);
    }
}
