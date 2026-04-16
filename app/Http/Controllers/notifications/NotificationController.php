<?php

namespace App\Http\Controllers\notifications;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($request->expectsJson() || $request->ajax()) {
            $notifications = UserNotification::where('user_id', $user->id)
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (UserNotification $n) => [
                    'id' => $n->id,
                    'title' => $n->title,
                    'description' => $n->description,
                    'reason' => $n->meta['reason']
                        ?? $n->meta['error']
                        ?? (in_array($n->type, ['danger', 'error', 'warning'], true) ? $n->description : null),
                    'details' => $n->meta['details'] ?? null,
                    'time' => optional($n->created_at)->toISOString(),
                    'unread' => ! $n->is_read,
                    'type' => $n->type,
                    'category' => $n->meta['category'] ?? 'system',
                ])
                ->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => UserNotification::where('user_id', $user->id)->where('is_read', false)->count(),
                ],
            ]);
        }

        return view('spa');
    }

    public function markRead(Request $request, int $id)
    {
        $notification = UserNotification::where('user_id', $request->user()->id)->findOrFail($id);
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['status' => 'success']);
    }

    public function markAllRead(Request $request)
    {
        UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['status' => 'success']);
    }

    public function destroy(Request $request, int $id)
    {
        $notification = UserNotification::where('user_id', $request->user()->id)->findOrFail($id);
        $notification->delete();

        return response()->json(['status' => 'success']);
    }
}
