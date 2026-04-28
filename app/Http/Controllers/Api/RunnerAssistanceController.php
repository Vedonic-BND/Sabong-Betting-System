<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\CashRequest;
use App\Events\CashRequestCreated;
use App\Events\RunnerAccepted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RunnerAssistanceController extends Controller
{
    /**
     * Teller requests assistance from runners
     */
    public function request(Request $request)
    {
        $request->validate([
            'request_type' => ['required', 'in:assistance,need_cash,collect_cash,other'],
            'custom_message' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        // Only tellers can request
        if ($user->role !== 'teller') {
            return response()->json(['message' => 'Only tellers can request assistance.'], 403);
        }

        // Get the message to display
        $message = $this->getRequestMessage($request->request_type, $request->custom_message);

        // Create a CashRequest record to trigger the broadcast event
        $cashRequest = CashRequest::create([
            'teller_id' => $user->id,
            'request_type' => $request->request_type,
            'custom_message' => $request->custom_message,
            'type' => 'cash_in',
            'amount' => 0,
            'status' => 'pending',
        ]);

        // Broadcast the event to all runners via WebSocket
        event(new CashRequestCreated($cashRequest));

        // Also save notifications to database for history
        $runners = User::where('role', 'runner')->get();
        foreach ($runners as $runner) {
            Notification::create([
                'user_id' => $runner->id,
                'title' => 'Assistance Needed',
                'message' => "{$user->name} - {$message}",
                'data' => json_encode([
                    'teller_id' => $user->id,
                    'teller_name' => $user->name,
                    'request_type' => $request->request_type,
                    'custom_message' => $request->custom_message,
                    'timestamp' => now()->timestamp,
                ]),
                'is_read' => false,
            ]);
        }

        return response()->json([
            'message' => 'Assistance request sent to all runners.',
        ], 201);
    }

    /**
     * Runner accepts assistance request from teller
     */
    public function accept(Request $request, $tellerId)
    {
        $user = $request->user();

        // Only runners can accept
        if ($user->role !== 'runner') {
            return response()->json(['message' => 'Only runners can accept requests.'], 403);
        }

        $teller = User::find($tellerId);
        if (!$teller || $teller->role !== 'teller') {
            return response()->json(['message' => 'Teller not found.'], 404);
        }

        // Check if teller is already assigned to another runner
        $cacheKey = "teller_assigned_{$tellerId}";
        if (Cache::has($cacheKey)) {
            $assignedRunner = Cache::get($cacheKey);
            return response()->json([
                'message' => "Teller is already assigned to {$assignedRunner['name']}.",
                'assigned_runner' => $assignedRunner['name'],
            ], 422);
        }

        // Assign this runner to the teller for 15 seconds
        Cache::put($cacheKey, [
            'id' => $user->id,
            'name' => $user->name,
        ], 15);

        // Create CashRequest record and broadcast event
        $cashRequest = CashRequest::create([
            'teller_id' => $teller->id,
            'runner_id' => $user->id,
            'request_type' => 'assistance',
            'type' => 'cash_in',
            'amount' => 0,
            'status' => 'accepted',
            'approved_at' => now(),
        ]);

        // Broadcast acceptance to other runners
        event(new RunnerAccepted($cashRequest));

        // Notify the teller
        Notification::create([
            'user_id' => $teller->id,
            'title' => 'Runner Accepted',
            'message' => "{$user->name} is on the way.",
            'data' => json_encode([
                'runner_id' => $user->id,
                'runner_name' => $user->name,
                'accepted_at' => now()->timestamp,
            ]),
            'is_read' => false,
        ]);

        // Notify other runners
        $otherRunners = User::where('role', 'runner')
            ->where('id', '!=', $user->id)
            ->get();

        foreach ($otherRunners as $runner) {
            Notification::create([
                'user_id' => $runner->id,
                'title' => 'Request Assigned',
                'message' => "{$user->name} has been assigned to {$teller->name}.",
                'data' => json_encode([
                    'assigned_runner_id' => $user->id,
                    'assigned_runner_name' => $user->name,
                    'teller_id' => $teller->id,
                    'teller_name' => $teller->name,
                ]),
                'is_read' => false,
            ]);
        }

        return response()->json([
            'message' => 'Request accepted. You are assigned for 15 seconds.',
            'assigned_until' => now()->addSeconds(15),
        ], 200);
    }

    /**
     * Get human-readable message based on request type
     */
    private function getRequestMessage(string $requestType, ?string $customMessage = null): string
    {
        return match ($requestType) {
            'assistance' => 'Assistance needed at counter',
            'need_cash' => 'Cash needed at counter',
            'collect_cash' => 'Need to collect excess cash',
            'other' => $customMessage ?? 'Custom assistance needed',
            default => 'Assistance needed',
        };
    }
}
