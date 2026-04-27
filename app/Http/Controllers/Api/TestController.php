<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Create a test notification for debugging
     * GET /api/test/create-notification
     */
    public function createTestNotification(Request $request)
    {
        $user = $request->user();

        // Create a test notification for the current user
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => 'TEST: Assistance Needed',
            'message' => 'This is a test notification - Teller requesting assistance',
            'data' => json_encode([
                'teller_id' => 1,
                'teller_name' => 'Test Teller',
                'request_type' => 'assistance',
                'custom_message' => '',
                'timestamp' => now()->timestamp,
            ]),
            'is_read' => false,
        ]);

        return response()->json([
            'message' => 'Test notification created',
            'notification' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
            ]
        ], 201);
    }

    /**
     * Get all notifications for the current user (for debugging)
     * GET /api/test/notifications
     */
    public function listNotifications(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'total' => $notifications->count(),
            'notifications' => $notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => $n->message,
                    'data' => $n->data,
                    'is_read' => $n->is_read,
                    'created_at' => $n->created_at,
                ];
            })
        ]);
    }
}
