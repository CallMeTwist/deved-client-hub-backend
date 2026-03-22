<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = UserNotification::where('user_id', $request->user()->id)
            ->orderByDesc('occurred_at')
            ->limit(30)
            ->get();

        $unreadCount = UserNotification::where('user_id', $request->user()->id)
            ->where('read', false)
            ->count();

        return response()->json([
            'data'         => $notifications->map(fn ($n) => [
                'id'          => $n->id,
                'type'        => $n->type,
                'title'       => $n->title,
                'message'     => $n->message,
                'link'        => $n->link,
                'read'        => $n->read,
                'occurred_at' => $n->occurred_at?->toISOString(),
            ]),
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * PATCH /api/notifications/{id}/read
     */
    public function markRead(Request $request, UserNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->update(['read' => true]);

        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * PATCH /api/notifications/read-all
     */
    public function markAllRead(Request $request): JsonResponse
    {
        UserNotification::where('user_id', $request->user()->id)
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * DELETE /api/notifications/{id}
     */
    public function destroy(Request $request, UserNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification dismissed.']);
    }
}
