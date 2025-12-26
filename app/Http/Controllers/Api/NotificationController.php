<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $limit = min((int) $request->get('per_page', 20), 50);
        $afterId = (int) $request->get('after_id', 0);
        $type = $request->get('type');
        $grouped = $request->get('grouped', false);

        if ($grouped) {
            $notifications = $this->notificationService->groupSimilarNotifications($user);
        } else {
            $notifications = $this->notificationService->getNotifications(
                $user,
                $limit,
                $afterId,
                $type
            );
        }

        $unreadCounts = $this->notificationService->getUnreadCountsByType($user);

        return response()->json([
            'notifications' => $grouped 
                ? $notifications 
                : NotificationResource::collection($notifications),
            'unread_count' => array_sum($unreadCounts),
            'unread_counts_by_type' => $unreadCounts,
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $notification = Notification::query()
            ->with(['actor', 'relatedPost'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $this->notificationService->markAsRead($notification);

        return response()->json([
            'notification' => new NotificationResource($notification),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        $this->notificationService->markAsRead($notification);

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'message' => 'All notifications marked as read',
            'count' => $count,
        ]);
    }

    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => ['required', 'array'],
            'notification_ids.*' => ['integer'],
        ]);

        $user = $request->user();
        $count = $this->notificationService->markMultipleAsRead(
            $user,
            $request->input('notification_ids')
        );

        return response()->json([
            'message' => 'Notifications marked as read',
            'count' => $count,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $notification = Notification::where('user_id', $user->id)->findOrFail($id);
        $this->notificationService->deleteNotification($notification);

        return response()->json(null, 204);
    }

    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'notification_ids' => ['required', 'array'],
            'notification_ids.*' => ['integer'],
        ]);

        $user = $request->user();
        $count = $this->notificationService->deleteMultiple(
            $user,
            $request->input('notification_ids')
        );

        return response()->json([
            'message' => 'Notifications deleted',
            'count' => $count,
        ]);
    }

    public function clearAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->clearAll($user);

        return response()->json([
            'message' => 'All notifications cleared',
            'count' => $count,
        ]);
    }

    public function getUnreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->get('type');
        
        $count = $this->notificationService->getUnreadCount($user, $type);
        $countsByType = $this->notificationService->getUnreadCountsByType($user);

        return response()->json([
            'unread_count' => $count,
            'unread_counts_by_type' => $countsByType,
        ]);
    }
}
