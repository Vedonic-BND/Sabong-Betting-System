<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    // GET /api/devices — List all active devices for the authenticated user
    public function index(Request $request)
    {
        $devices = DeviceToken::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($device) => [
                'id'           => $device->id,
                'device_name'  => $device->device_name,
                'device_os'    => $device->device_os,
                'device_ip'    => $device->device_ip,
                'last_used_at' => $device->last_used_at,
                'created_at'   => $device->created_at,
            ]);

        return response()->json($devices);
    }

    // DELETE /api/devices/{id} — Revoke a specific device token
    public function revoke(Request $request, DeviceToken $device)
    {
        // Ensure the device belongs to the authenticated user
        if ($device->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Delete the Sanctum token associated with this device
        $request->user()->tokens()
            ->where('name', $device->device_name)
            ->delete();

        // Delete the device record
        $device->delete();

        return response()->json(['message' => 'Device revoked.']);
    }

    // POST /api/devices/revoke-all — Revoke all devices except current
    public function revokeAll(Request $request)
    {
        $currentTokenHash = hash('sha256', $request->bearerToken());

        // Get all other device tokens
        $otherDevices = DeviceToken::where('user_id', $request->user()->id)
            ->where('token_hash', '!=', $currentTokenHash)
            ->get();

        // Revoke each token
        foreach ($otherDevices as $device) {
            $request->user()->tokens()
                ->where('name', $device->device_name)
                ->delete();
            $device->delete();
        }

        return response()->json(['message' => 'All other devices revoked.']);
    }
}

