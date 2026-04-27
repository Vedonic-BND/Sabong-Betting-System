<?php

namespace App\Http\Controllers\Api;

use App\Events\CashRequestCreated;
use App\Events\RunnerAccepted;
use App\Http\Controllers\Controller;
use App\Models\CashRequest;
use App\Models\Notification;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class CashRequestController extends Controller
{
    /**
     * Generate a user-friendly message based on request type
     */
    private function getRequestMessage(string $requestType, ?string $customMessage = null): string
    {
        return match ($requestType) {
            'assistance' => 'Assistance needed at counter',
            'need_cash' => 'Runner needed - Need cash',
            'collect_cash' => 'Runner needed - Collect excess cash',
            'other' => "Custom request: {$customMessage}",
            default => 'Runner assistance needed',
        };
    }

    // POST /api/cash-request — Teller requests cash
    public function store(Request $request)
    {
        $request->validate([
            'type'            => ['required', 'in:cash_in,cash_out'],
            'amount'          => ['nullable', 'numeric', 'min:0'],
            'reason'          => ['nullable', 'string', 'max:255'],
            'request_type'    => ['required', 'in:assistance,need_cash,collect_cash,other'],
            'custom_message'  => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        // Only tellers can create cash requests
        if ($user->role !== 'teller') {
            return response()->json(['message' => 'Only tellers can request cash.'], 403);
        }

        $customMessage = $request->request_type === 'other' ? $request->custom_message : null;

        $cashRequest = CashRequest::create([
            'teller_id'      => $user->id,
            'type'           => $request->type,
            'amount'         => $request->amount ?? 0,
            'reason'         => $request->reason,
            'request_type'   => $request->request_type,
            'custom_message' => $customMessage,
            'status'         => 'pending',
        ]);

        AuditLogger::log('created_cash_request', 'cash_request', $cashRequest->id, [
            'type'         => $request->type,
            'amount'       => $request->amount,
            'request_type' => $request->request_type,
        ]);

        // Broadcast to runners and owner
        broadcast(new CashRequestCreated($cashRequest));

        // Save notification for all runners
        $runners = \App\Models\User::where('role', 'runner')->get();
        foreach ($runners as $runner) {
            $messageText = $this->getRequestMessage($request->request_type, $customMessage);

            Notification::create([
                'user_id' => $runner->id,
                'title' => 'Runner Request',
                'message' => $messageText,
                'data' => json_encode([
                    'teller_id' => $user->id,
                    'teller_name' => $user->name,
                    'cash_request_id' => $cashRequest->id,
                    'type' => $cashRequest->type,
                    'amount' => $cashRequest->amount,
                    'request_type' => $cashRequest->request_type,
                    'custom_message' => $customMessage,
                ]),
                'is_read' => false,
            ]);
        }

        return response()->json([
            'message'  => 'Cash request created.',
            'id'       => $cashRequest->id,
            'status'   => $cashRequest->status,
        ], 201);
    }

    // GET /api/cash-requests — Get all pending cash requests (for runner and owner)
    public function index(Request $request)
    {
        $user = $request->user();

        // Only runners and owner can view all requests
        if (!in_array($user->role, ['runner', 'owner', 'admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $requests = CashRequest::with(['teller', 'runner', 'approvedBy', 'completedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $requests->map(function ($cashRequest) {
                return [
                    'id'           => $cashRequest->id,
                    'teller_name'  => $cashRequest->teller->name,
                    'runner_name'  => $cashRequest->runner?->name,
                    'type'         => $cashRequest->type,
                    'amount'       => $cashRequest->amount,
                    'reason'       => $cashRequest->reason,
                    'status'       => $cashRequest->status,
                    'created_at'   => $cashRequest->created_at->format('M d, Y h:i A'),
                    'approved_at'  => $cashRequest->approved_at ? $cashRequest->approved_at->format('M d, Y h:i A') : null,
                    'completed_at' => $cashRequest->completed_at ? $cashRequest->completed_at->format('M d, Y h:i A') : null,
                ];
            }),
        ], 200);
    }

    // GET /api/cash-request/{id} — Get specific cash request
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $cashRequest = CashRequest::with(['teller', 'runner', 'approvedBy', 'completedBy'])->find($id);

        if (!$cashRequest) {
            return response()->json(['message' => 'Cash request not found.'], 404);
        }

        // Only teller who created it, runners, or owner can view
        if ($user->id !== $cashRequest->teller_id && !in_array($user->role, ['runner', 'owner', 'admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'id'           => $cashRequest->id,
            'teller_name'  => $cashRequest->teller->name,
            'runner_name'  => $cashRequest->runner?->name,
            'type'         => $cashRequest->type,
            'amount'       => $cashRequest->amount,
            'reason'       => $cashRequest->reason,
            'status'       => $cashRequest->status,
            'created_at'   => $cashRequest->created_at->format('M d, Y h:i A'),
            'approved_at'  => $cashRequest->approved_at ? $cashRequest->approved_at->format('M d, Y h:i A') : null,
            'completed_at' => $cashRequest->completed_at ? $cashRequest->completed_at->format('M d, Y h:i A') : null,
        ], 200);
    }

    // PATCH /api/cash-request/{id}/approve — Runner approves the request
    public function approve(Request $request, $id)
    {
        $user = $request->user();

        // Only runners and admin can approve
        if (!in_array($user->role, ['runner', 'admin'])) {
            return response()->json(['message' => 'Only runners can approve cash requests.'], 403);
        }

        $cashRequest = CashRequest::find($id);

        if (!$cashRequest) {
            return response()->json(['message' => 'Cash request not found.'], 404);
        }

        if ($cashRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending requests can be approved.'], 422);
        }

        $cashRequest->update([
            'runner_id'  => $user->id,
            'status'     => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        AuditLogger::log('approved_cash_request', 'cash_request', $cashRequest->id, [
            'runner' => $user->name,
        ]);

        // Broadcast to teller that runner accepted the request
        broadcast(new RunnerAccepted($cashRequest));

        // Save notification for the teller
        Notification::create([
            'user_id' => $cashRequest->teller_id,
            'title' => 'Runner Accepted',
            'message' => "{$user->name} is on the way to assist you.",
            'data' => json_encode([
                'runner_id' => $user->id,
                'runner_name' => $user->name,
                'cash_request_id' => $cashRequest->id,
                'type' => $cashRequest->type,
                'amount' => $cashRequest->amount,
            ]),
            'is_read' => false,
        ]);

        // Notify other runners that this runner is assigned
        $otherRunners = \App\Models\User::where('role', 'runner')
            ->where('id', '!=', $user->id)
            ->get();

        foreach ($otherRunners as $runner) {
            Notification::create([
                'user_id' => $runner->id,
                'title' => 'Request Assigned',
                'message' => "{$user->name} has been assigned to teller {$cashRequest->teller->name}.",
                'data' => json_encode([
                    'assigned_runner_id' => $user->id,
                    'assigned_runner_name' => $user->name,
                    'teller_id' => $cashRequest->teller_id,
                    'cash_request_id' => $cashRequest->id,
                ]),
                'is_read' => false,
            ]);
        }

        // Dispatch job to reset assignment after 30 seconds if not completed
        dispatch(new \App\Jobs\ResetRunnerAssignment($cashRequest->id))
            ->delay(now()->addSeconds(30));

        return response()->json([
            'message' => 'Cash request approved.',
            'status'  => $cashRequest->status,
        ], 200);
    }

    // PATCH /api/cash-request/{id}/complete — Runner completes the transaction
    public function complete(Request $request, $id)
    {
        $user = $request->user();

        // Only runners and admin can complete
        if (!in_array($user->role, ['runner', 'admin'])) {
            return response()->json(['message' => 'Only runners can complete cash requests.'], 403);
        }

        $cashRequest = CashRequest::find($id);

        if (!$cashRequest) {
            return response()->json(['message' => 'Cash request not found.'], 404);
        }

        if ($cashRequest->status !== 'approved') {
            return response()->json(['message' => 'Only approved requests can be completed.'], 422);
        }

        $cashRequest->update([
            'status'        => 'completed',
            'completed_at'  => now(),
            'completed_by'  => $user->id,
        ]);

        AuditLogger::log('completed_cash_request', 'cash_request', $cashRequest->id, [
            'runner' => $user->name,
            'amount' => $cashRequest->amount,
        ]);

        return response()->json([
            'message' => 'Cash request completed.',
            'status'  => $cashRequest->status,
        ], 200);
    }

    // PATCH /api/cash-request/{id}/reject — Runner rejects the request
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        // Only runners and admin can reject
        if (!in_array($user->role, ['runner', 'admin'])) {
            return response()->json(['message' => 'Only runners can reject cash requests.'], 403);
        }

        $cashRequest = CashRequest::find($id);

        if (!$cashRequest) {
            return response()->json(['message' => 'Cash request not found.'], 404);
        }

        if ($cashRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending requests can be rejected.'], 422);
        }

        $cashRequest->update([
            'status' => 'rejected',
            'reason' => $request->reason ?? $cashRequest->reason,
        ]);

        AuditLogger::log('rejected_cash_request', 'cash_request', $cashRequest->id, [
            'runner' => $user->name,
        ]);

        return response()->json([
            'message' => 'Cash request rejected.',
            'status'  => $cashRequest->status,
        ], 200);
    }
}
