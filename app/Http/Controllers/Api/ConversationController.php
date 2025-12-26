<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    protected ConversationService $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $limit = min((int) $request->get('per_page', 20), 50);
        $afterId = (int) $request->get('after_id');

        $conversations = $this->conversationService->getUserConversations($user, $limit, $afterId);

        $conversationsWithUnread = $conversations->map(function ($conversation) use ($user) {
            return [
                ...ConversationResource::make($conversation)->toArray(request()),
                'unread_count' => $conversation->getUnreadCountForUser($user->id),
            ];
        });

        $totalUnread = $this->conversationService->getTotalUnreadCount($user);

        return response()->json([
            'conversations' => $conversationsWithUnread,
            'total_unread_count' => $totalUnread,
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);

        $conversation = $this->conversationService->getConversation($conversation, $user);

        $this->conversationService->markConversationAsRead($conversation, $user);

        return response()->json([
            'conversation' => new ConversationResource($conversation),
            'unread_count' => 0,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();
        $recipient = User::findOrFail($request->user_id);

        $message = $this->conversationService->startNewConversation(
            $user,
            $recipient,
            $request->message
        );

        return response()->json([
            'message' => 'Conversation started',
            'conversation' => new ConversationResource($message->conversation->load(['participants'])),
            'message' => new MessageResource($message),
        ], 201);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);

        $this->conversationService->markConversationAsRead($conversation, $user);

        return response()->json([
            'message' => 'Conversation marked as read',
        ]);
    }

    public function leave(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);

        $this->conversationService->leaveConversation($conversation, $user);

        return response()->json([
            'message' => 'Left conversation',
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $conversation = Conversation::findOrFail($id);

        $this->leaveConversation($conversation, $user);

        return response()->json(null, 204);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $user = $request->user();
        $query = $request->get('q');
        $limit = min((int) $request->get('per_page', 20), 50);

        $messages = $this->conversationService->searchMessages($user, $query, $limit);

        return response()->json([
            'query' => $query,
            'messages' => MessageResource::collection($messages),
        ]);
    }
}
