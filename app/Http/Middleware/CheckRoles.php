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

        if (!$user) {
            abort(403, 'Not authenticated.');
        }

        // Handle both comma and pipe separators
        $allowedRoles = array_map('trim', preg_split('/[,|]/', $roles));

        // Make role comparison case-insensitive
        $userRole = strtolower($user->role ?? '');
        $allowedRoles = array_map('strtolower', $allowedRoles);

        // Debug log
        \Log::info('CheckRoles Middleware Debug', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'allowed_roles_param' => $roles,
            'parsed_allowed_roles' => $allowedRoles,
            'path' => $request->path(),
            'is_authorized' => in_array($userRole, $allowedRoles),
        ]);

        if (!in_array($userRole, $allowedRoles)) {
            abort(403, "Unauthorized. User role '{$user->role}' not in allowed roles: {$roles}");
        }

        return $next($request);
    }
}
