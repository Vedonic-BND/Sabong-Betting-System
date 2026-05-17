<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationSent;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class TestBroadcastController extends Controller
{
    /**
     * Test broadcasting a notification to a specific user
     */
    public function testNotification(Request $request)
    {
        $userId = $request->input('user_id', 1);

        $notification = Notification::create([
            'user_id' => $userId,
            'title' => 'Test Notification',
            'message' => "This is a test notification for user $userId",
            'data' => json_encode([
                'test' => true,
                'timestamp' => now(),
            ]),
            'is_read' => false,
        ]);

        \Log::info("Test: Broadcasting notification to user $userId");
        broadcast(new NotificationSent($notification))->toOthers();

        return response()->json([
            'message' => 'Test notification broadcasted',
            'notification_id' => $notification->id,
            'user_id' => $userId,
        ]);
    }
}
