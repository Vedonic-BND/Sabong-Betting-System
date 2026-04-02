<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // only admin and teller can use the app
        if (!in_array($user->role, ['admin', 'teller'])) {
            return response()->json([
                'message' => 'Access denied.',
            ], 403);
        }

        // for teller — create both tokens
        // for admin — create admin token only
        if ($user->role === 'teller') {
            // delete old teller tokens
            $user->tokens()->whereIn('name', ['cashin', 'cashout'])->delete();

            $cashInToken  = $user->createToken('cashin')->plainTextToken;
            $cashOutToken = $user->createToken('cashout')->plainTextToken;

            return response()->json([
                'role'          => 'teller',
                'cashin_token'  => $cashInToken,
                'cashout_token' => $cashOutToken,
                'user'          => [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
            ]);
        }

        // admin
        $user->tokens()->where('name', 'admin')->delete();
        $token = $user->createToken('admin')->plainTextToken;

        return response()->json([
            'role'  => 'admin',
            'token' => $token,
            'user'  => [
                'id'   => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
