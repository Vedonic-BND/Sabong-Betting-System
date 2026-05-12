<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
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
        $successfulAssignments = Notification::where('user_id', $user->id)
            ->where('title', 'Assignment Successful')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all "Assistance Needed" notifications from tellers to runners (for monitoring active requests)
        $assistanceNotifications = Notification::where('title', 'Assistance Needed')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                // Extract teller info from notification data
                $data = $notification->data ?? [];
                $tellerId = $data['teller_id'] ?? null;

                // Check if a runner is currently assigned to this teller
                $assignmentKey = "teller_assigned_{$tellerId}";
                $assignedRunner = Cache::get($assignmentKey);

                // Get teller user details
                $teller = User::find($tellerId);

                return (object)[
                    'notification' => $notification,
                    'teller' => $teller,
                    'teller_id' => $tellerId,
                    'request_type' => $data['request_type'] ?? 'assistance',
                    'custom_message' => $data['custom_message'] ?? null,
                    'assigned_runner' => $assignedRunner,
                    'is_active' => $assignedRunner !== null,
                    'created_at' => $notification->created_at,
                ];
            });

        // Count unread notifications
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->whereIn('title', ['Assignment Successful', 'Assistance Needed'])
            ->count();

        return view('owner.notifications.index', compact('assistanceNotifications', 'successfulAssignments', 'unreadCount'));
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
     * Clear all notifications for the current user
     */
    public function clear(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->delete();

        return redirect()->route('owner.notifications.index')->with('success', 'All notifications cleared.');
    }
}
