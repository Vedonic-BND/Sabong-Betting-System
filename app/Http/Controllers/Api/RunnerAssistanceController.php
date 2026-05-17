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

        // Check if teller is in cooldown (just accepted a request 30 seconds ago)
        $tellerCooldownKey = "teller_cooldown_{$user->id}";
        if (Cache::has($tellerCooldownKey)) {
            // Default to 30 seconds if we can't get exact remaining time
            return response()->json([
                'message' => 'Please wait before sending another request.',
                'retry_after_seconds' => 30,
            ], 429); // Too Many Requests
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

        // Broadcast the event to all runners via WebSocket (no database save until acceptance)
        event(new CashRequestCreated($cashRequest));

        // Save "Assistance Needed" notification for owner to see
        $owner = User::where('role', 'owner')->first();
        if ($owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title' => 'Assistance Needed',
                'message' => "{$user->name} is requesting assistance",
                'data' => json_encode([
                    'teller_id' => $user->id,
                    'teller_name' => $user->name,
                    'request_type' => $request->request_type,
                    'custom_message' => $request->custom_message,
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

        \Log::info("Accept request - Runner ID: {$user->id}, Teller ID: {$tellerId}");

        // Only runners can accept
        if ($user->role !== 'runner') {
            \Log::warning("Accept rejected - user is not runner: {$user->role}");
            return response()->json(['message' => 'Only runners can accept requests.'], 403);
        }

        $teller = User::find($tellerId);
        if (!$teller || $teller->role !== 'teller') {
            \Log::warning("Accept rejected - teller not found or not teller role: {$tellerId}");
            return response()->json(['message' => 'Teller not found.'], 404);
        }

        // Check if someone already accepted this request (prevents race condition)
        $lockKey = "teller_request_lock_{$tellerId}";
        $assignmentKey = "teller_assigned_{$tellerId}";

        // Try to acquire lock
        $assignedRunner = Cache::get($assignmentKey);
        if ($assignedRunner !== null) {
            \Log::warning("Request already accepted by runner: {$assignedRunner['id']} for teller: {$tellerId}");
            return response()->json([
                'message' => 'Request already accepted by another runner.',
                'assigned_to' => $assignedRunner['name'],
            ], 409); // Conflict
        }

        // Atomically set the assignment (only if not already set)
        $isSet = Cache::add($assignmentKey, [
            'id' => $user->id,
            'name' => $user->name,
        ], 30); // 30 seconds timeout

        if (!$isSet) {
            \Log::warning("Failed to acquire lock for teller {$tellerId} - already assigned");
            $assignedRunner = Cache::get($assignmentKey);
            return response()->json([
                'message' => 'Request already accepted by another runner.',
                'assigned_to' => $assignedRunner['name'] ?? 'Unknown',
            ], 409); // Conflict
        }

        // Also block the teller from sending new requests for 30 seconds
        $tellerLockKey = "teller_cooldown_{$tellerId}";
        Cache::put($tellerLockKey, true, 30);

        // Create CashRequest record and broadcast event
        $cashRequest = CashRequest::create([
            'teller_id' => $teller->id,
            'runner_id' => $user->id,
            'request_type' => 'assistance',
            'type' => 'cash_in',
            'amount' => 0,
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        // Broadcast acceptance to other runners (no database save for other runners' notifications)
        event(new RunnerAccepted($cashRequest));

        // Save notification only for the teller - record the successful acceptance
        Notification::create([
            'user_id' => $teller->id,
            'title' => 'Runner Accepted',
            'message' => "{$user->name} is on the way.",
            'data' => json_encode([
                'teller_id' => $teller->id,
                'teller_name' => $teller->name,
                'runner_id' => $user->id,
                'runner_name' => $user->name,
                'accepted_at' => now()->timestamp,
            ]),
            'is_read' => false,
        ]);

        // Save notification for the runner - record their successful acceptance
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Request Accepted',
            'message' => "You have accepted the request from {$teller->name}.",
            'data' => json_encode([
                'teller_id' => $teller->id,
                'teller_name' => $teller->name,
                'runner_id' => $user->id,
                'runner_name' => $user->name,
                'accepted_at' => now()->timestamp,
            ]),
            'is_read' => false,
        ]);

        // Save notification to all owners - record successful assignment for monitoring
        $owners = User::where('role', 'owner')->get();
        foreach ($owners as $owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title' => 'Assignment Successful',
                'message' => "{$user->name} assigned to {$teller->name}",
                'data' => json_encode([
                    'teller_id' => $teller->id,
                    'teller_name' => $teller->name,
                    'runner_id' => $user->id,
                    'runner_name' => $user->name,
                    'request_type' => 'assistance',
                    'assigned_at' => now()->timestamp,
                ]),
                'is_read' => false,
            ]);
        }

        return response()->json([
            'message' => 'Request accepted. You are assigned for 30 seconds.',
            'assigned_until' => now()->addSeconds(30),
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
