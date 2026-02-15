<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);

        $query = Notification::where('user_id', $user->id)->orderByDesc('created_at');

        if ($request->has('unread') && $request->unread) {
            $query->where('is_read', false);
        }

        $notifications = $query->paginate($perPage);

        return $this->sendPaginatedResponse($notifications);
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== request()->user()->id) {
            return $this->sendError('Anda tidak memiliki akses.', null, 403);
        }

        $notification->update(['is_read' => true, 'read_at' => now()]);

        return $this->sendResponse($notification, 'Notifikasi telah dibaca.');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return $this->sendResponse(null, 'Semua notifikasi telah dibaca.');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return $this->sendResponse(['count' => $count]);
    }
}
