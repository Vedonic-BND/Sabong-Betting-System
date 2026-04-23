<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username'    => ['required', 'string'],
            'password'    => ['required', 'string'],
            'device_name' => ['sometimes', 'string'],
            'device_os'   => ['sometimes', 'string'], // iOS, Android, Web
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

        // for teller — enforce 2-device limit
        if ($user->role === 'teller') {
            // Check active devices (max 2)
            $activeDevices = DeviceToken::where('user_id', $user->id)->count();

            if ($activeDevices >= 2) {
                // Delete oldest device token to make room for new one
                $oldest = DeviceToken::where('user_id', $user->id)
                    ->orderBy('created_at')
                    ->first();

                // Revoke the oldest token from Sanctum
                $user->tokens()
                    ->where('name', $oldest->device_name ?? 'teller')
                    ->delete();

                $oldest->delete();
            }

            // Create new token with cashin ability
            $token = $user->createToken('teller', ['cashin'])->plainTextToken;
            $plainToken = $token;
            $tokenHash = hash('sha256', $plainToken);

            // Store device info
            DeviceToken::create([
                'user_id'      => $user->id,
                'token_hash'   => $tokenHash,
                'device_name'  => $request->device_name ?? 'Unknown Device',
                'device_os'    => $request->device_os ?? 'Unknown',
                'device_ip'    => $request->ip(),
            ]);

            return response()->json([
                'role'      => 'teller',
                'token'     => $plainToken,
                'user'      => [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
            ]);
        }

        // admin — no device limit
        $token = $user->createToken('admin', ['admin'])->plainTextToken;

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

        // Clean up device token record if exists
        $token = $request->bearerToken();
        if ($token) {
            $tokenHash = hash('sha256', $token);
            DeviceToken::where('token_hash', $tokenHash)->delete();
        }

        return response()->json(['message' => 'Logged out.']);
    }
}
