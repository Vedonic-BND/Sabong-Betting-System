<?php

namespace App\Http\Controllers\Api;

use App\Events\NotificationSent;
use App\Events\TellerCashStatusUpdated;
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

    /**
     * Test broadcasting TellerCash update (for financial overview testing)
     */
    public function testTellerCashBroadcast(Request $request)
    {
        \Log::info('🧪 TEST: Starting TellerCash broadcast test');

        try {
            $tellerId = $request->input('teller_id', 2);
            $amount = $request->input('amount', 1000);

            \Log::info("🧪 TEST: Broadcasting TellerCash update for teller $tellerId");

            // Broadcast the event
            broadcast(new TellerCashStatusUpdated(
                $tellerId,
                "Test Teller $tellerId",
                $amount,
                'test_broadcast',
                $amount
            ))->toOthers();

            \Log::info('🧪 TEST: Broadcast sent successfully');

            return response()->json([
                'success' => true,
                'message' => 'Test TellerCash broadcast sent',
                'event' => 'teller.cash-updated',
                'channel' => 'cash-status',
                'teller_id' => $tellerId,
                'amount' => $amount,
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('🧪 TEST: Broadcast failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
