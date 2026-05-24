<?php

namespace App\Http\Controllers;

use App\Events\TellerCashStatusUpdated;
use Illuminate\Http\Request;

class TestBroadcastController extends Controller
{
    /**
     * Test broadcast endpoint - sends a test event
     */
    public function test()
    {
        // Send a test broadcast
        broadcast(new TellerCashStatusUpdated(
            1,
            'Test User',
            1000,
            'test',
            0
        ))->toOthers();

        return response()->json([
            'message' => 'Test broadcast sent!',
            'event' => 'teller.cash-updated',
            'channel' => 'cash-status',
            'timestamp' => now(),
        ]);
    }
}
