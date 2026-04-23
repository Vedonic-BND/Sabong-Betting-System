<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRoles
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = Auth::user();
        $allowedRoles = array_map('trim', explode(',', $roles));

        if (!$user || !in_array($user->role, $allowedRoles)) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
