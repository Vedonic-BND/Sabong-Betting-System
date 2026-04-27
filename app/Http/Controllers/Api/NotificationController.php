<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // POST /api/notifications - Save a notification
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'data' => ['nullable', 'json'],
        ]);

        $notification = \App\Models\Notification::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'data' => $validated['data'] ?? null,
            'is_read' => false,
        ]);

        return response()->json([
            'id' => $notification->id,
            'message' => 'Notification saved successfully',
        ], 201);
    }

    // GET /api/notifications - Get user's notifications
    public function index(Request $request)
    {
        $notifications = \App\Models\Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'timestamp' => $notification->created_at->format('h:i a'),
                ];
            });

        return response()->json($notifications);
    }

    // PATCH /api/notifications/{id}/read - Mark notification as read
    public function markAsRead(Request $request, $id)
    {
        $notification = \App\Models\Notification::where('user_id', $request->user()->id)
            ->find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    // DELETE /api/notifications - Clear all notifications
    public function clear(Request $request)
    {
        \App\Models\Notification::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'All notifications cleared']);
    }
}
