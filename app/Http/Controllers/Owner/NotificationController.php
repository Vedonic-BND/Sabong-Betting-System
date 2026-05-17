<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\CashRequest;
use App\Models\Notification;
use App\Models\User;
use App\Events\RunnerAccepted;
use App\Events\RunnerAssignedByOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    /**
     * Display a listing of all teller-to-runner assistance requests with assignments
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all "Assignment Successful" notifications for this owner
        $allNotifications = Notification::where('user_id', $user->id)
            ->where('title', 'Assignment Successful')
            ->orderBy('created_at', 'desc')
            ->get();

        // Apply filters in PHP for JSON data
        $successfulAssignments = $allNotifications->filter(function ($notification) use ($request) {
            $data = json_decode($notification->data, true) ?? [];

            // Filter by runner name
            if ($request->runner_name) {
                $runnerName = $data['runner_name'] ?? '';
                if (stripos($runnerName, $request->runner_name) === false) {
                    return false;
                }
            }

            // Filter by teller name
            if ($request->teller_name) {
                $tellerName = $data['teller_name'] ?? '';
                if (stripos($tellerName, $request->teller_name) === false) {
                    return false;
                }
            }

            // Filter by request type
            if ($request->request_type) {
                $requestType = $data['request_type'] ?? '';
                if ($requestType !== $request->request_type) {
                    return false;
                }
            }

            // Filter by date range
            if ($request->date_from) {
                if ($notification->created_at->format('Y-m-d') < $request->date_from) {
                    return false;
                }
            }

            if ($request->date_to) {
                if ($notification->created_at->format('Y-m-d') > $request->date_to) {
                    return false;
                }
            }

            return true;
        })->values();

        // Get all tellers that have active assignments (currently have runners assigned)
        $allTellers = User::where('role', 'teller')->get();
        $tellersWithAssignments = [];

        foreach ($allTellers as $teller) {
            $assignmentKey = "teller_assigned_{$teller->id}";
            $assignedRunner = Cache::get($assignmentKey);

            $tellersWithAssignments[] = (object)[
                'teller' => $teller,
                'teller_id' => $teller->id,
                'assigned_runner' => $assignedRunner,
                'is_active' => $assignedRunner !== null,
                'created_at' => now(),
            ];
        }

        // Filter to show only tellers WITHOUT active assignments (pending assignment)
        $availableTellers = collect($tellersWithAssignments)->filter(function ($item) {
            return !$item->is_active;
        })->values();

        // Count unread notifications
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->whereIn('title', ['Assignment Successful', 'Assistance Needed'])
            ->count();

        return view('owner.notifications.index', compact('availableTellers', 'successfulAssignments', 'unreadCount'));
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        // Check if the notification belongs to the current user
        if ($notification->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $notification->update(['is_read' => true]);

        return redirect()->route('owner.notifications.index')->with('success', 'Notification marked as read.');
    }

    /**
     * Delete a single notification
     */
    public function delete(Request $request, Notification $notification)
    {
        // Check if the notification belongs to the current user
        if ($notification->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $notification->delete();

        return redirect()->route('owner.notifications.index')->with('success', 'Notification deleted.');
    }

    /**
     * Export assignments to CSV
     */
    public function export(Request $request)
    {
        $user = $request->user();

        // Get all "Assignment Successful" notifications for this owner
        $allNotifications = Notification::where('user_id', $user->id)
            ->where('title', 'Assignment Successful')
            ->orderBy('created_at', 'desc')
            ->get();

        // Apply filters in PHP for JSON data (same logic as index)
        $assignments = $allNotifications->filter(function ($notification) use ($request) {
            $data = json_decode($notification->data, true) ?? [];

            // Filter by runner name
            if ($request->runner_name) {
                $runnerName = $data['runner_name'] ?? '';
                if (stripos($runnerName, $request->runner_name) === false) {
                    return false;
                }
            }

            // Filter by teller name
            if ($request->teller_name) {
                $tellerName = $data['teller_name'] ?? '';
                if (stripos($tellerName, $request->teller_name) === false) {
                    return false;
                }
            }

            // Filter by request type
            if ($request->request_type) {
                $requestType = $data['request_type'] ?? '';
                if ($requestType !== $request->request_type) {
                    return false;
                }
            }

            // Filter by date range
            if ($request->date_from) {
                if ($notification->created_at->format('Y-m-d') < $request->date_from) {
                    return false;
                }
            }

            if ($request->date_to) {
                if ($notification->created_at->format('Y-m-d') > $request->date_to) {
                    return false;
                }
            }

            return true;
        })->values();

        $filename = 'assignments-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($assignments) {
            $file = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // headers
            fputcsv($file, [
                'ID',
                'Runner',
                'Teller',
                'Request Type',
                'Date & Time',
            ]);

            foreach ($assignments as $assignment) {
                $data = json_decode($assignment->data, true) ?? [];
                fputcsv($file, [
                    $assignment->id,
                    $data['runner_name'] ?? '—',
                    $data['teller_name'] ?? '—',
                    ucfirst(str_replace('_', ' ', $data['request_type'] ?? '—')),
                    $assignment->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Clear all notifications for the current user
     */
    public function clear(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->delete();

        return redirect()->route('owner.notifications.index')->with('success', 'All notifications cleared.');
    }

    /**
     * Get all available runners for assignment
     */
    public function getAvailableRunners(Request $request)
    {
        $runners = User::where('role', 'runner')
            ->select('id', 'name')
            ->get();

        return response()->json($runners);
    }

    /**
     * Get message based on request type
     */
    private function getRequestMessage(string $requestType, ?string $customMessage = null): string
    {
        return match ($requestType) {
            'assistance' => 'Assistance needed',
            'need_cash' => 'Runner needed for cash',
            'collect_cash' => 'Runner needed to collect cash',
            'other' => "Custom request: {$customMessage}",
            default => 'Assistance needed',
        };
    }

    /**
     * Manually assign a runner to a teller
     */
    public function assignRunner(Request $request, $tellerId)
    {
        $request->validate([
            'runner_id' => ['required', 'integer', 'exists:users,id'],
            'request_type' => ['required', 'in:assistance,need_cash,collect_cash,other'],
        ]);

        $teller = User::find($tellerId);
        $runner = User::find($request->runner_id);

        if (!$teller || $teller->role !== 'teller') {
            return response()->json(['message' => 'Invalid teller'], 404);
        }

        if (!$runner || $runner->role !== 'runner') {
            return response()->json(['message' => 'Invalid runner'], 404);
        }

        // Set the assignment in cache (auto-expires)
        $assignmentKey = "teller_assigned_{$tellerId}";
        Cache::put($assignmentKey, [
            'id' => $runner->id,
            'name' => $runner->name,
        ], now()->addSeconds(30));

        // Get the message based on request type
        $message = $this->getRequestMessage($request->request_type);

        // Create a CashRequest record to simulate the acceptance
        $cashRequest = CashRequest::create([
            'teller_id' => $tellerId,
            'runner_id' => $runner->id,
            'request_type' => $request->request_type,
            'type' => 'cash_in',
            'amount' => 0,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // Broadcast the RunnerAccepted event on cash-requests channel
        // This will trigger the same notification popup as when a runner accepts a request
        event(new RunnerAccepted($cashRequest));

        // Broadcast to runner that they have been assigned by owner
        event(new RunnerAssignedByOwner($cashRequest));

        // Save notification for teller that runner is on the way
        Notification::create([
            'user_id' => $tellerId,
            'title' => 'Runner Assigned',
            'message' => "{$runner->name} is on the way. {$message}.",
            'data' => json_encode([
                'runner_id' => $runner->id,
                'runner_name' => $runner->name,
                'request_type' => $request->request_type,
            ]),
            'is_read' => false,
        ]);

        // Save notification for runner about the assignment
        Notification::create([
            'user_id' => $runner->id,
            'title' => 'New Assignment',
            'message' => "You have been assigned to assist {$teller->name} - {$message}",
            'data' => json_encode([
                'teller_id' => $tellerId,
                'teller_name' => $teller->name,
                'request_type' => $request->request_type,
            ]),
            'is_read' => false,
        ]);

        // Save notification for owner about the successful assignment
        $owner = User::where('role', 'owner')->first();
        if ($owner) {
            Notification::create([
                'user_id' => $owner->id,
                'title' => 'Assignment Successful',
                'message' => "{$runner->name} has been assigned to assist {$teller->name} - {$message}",
                'data' => json_encode([
                    'runner_id' => $runner->id,
                    'runner_name' => $runner->name,
                    'teller_id' => $tellerId,
                    'teller_name' => $teller->name,
                    'request_type' => $request->request_type,
                ]),
                'is_read' => false,
            ]);
        }

        return response()->json([
            'message' => 'Runner assigned successfully',
            'runner' => ['id' => $runner->id, 'name' => $runner->name],
        ]);
    }
}
