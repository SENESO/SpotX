<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    protected ConversationService $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    public function index(Request $request, string $conversationId): JsonResponse
    {
        $user = $request->user();
        
        $limit = min((int) $request->get('per_page', 50), 100);
        $beforeId = (int) $request->get('before_id');

        $conversation = Conversation::findOrFail($conversationId);
        
        $messages = $this->conversationService->getMessages(
            $conversation,
            $user,
            $limit,
            $beforeId ?: null
        );

        return response()->json([
            'messages' => MessageResource::collection($messages),
            'pagination' => [
                'has_more' => $messages->count() === $limit,
                'next_cursor' => $messages->count() === $limit ? $messages->last()->id : null,
            ],
        ]);
    }

    public function store(Request $request, string $conversationId): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'type' => ['sometimes', 'string', 'in:text,image,file'],
            'attachments' => ['sometimes', 'array'],
        ]);

        $user = $request->user();
        $conversation = Conversation::findOrFail($conversationId);

        $message = $this->conversationService->sendMessage(
            $conversation,
            $user,
            $request->input('content'),
            $request->input('type', 'text'),
            $request->input('attachments', [])
        );

        return response()->json([
            'message' => 'Message sent',
            'message' => new MessageResource($message->load('user')),
        ], 201);
    }

    public function show(Request $request, string $conversationId, string $messageId): JsonResponse
    {
        $user = $request->user();
        
        $message = Message::where('conversation_id', $conversationId)
            ->findOrFail($messageId);

        $this->conversationService->markMessageAsRead($message, $user);

        return response()->json([
            'message' => new MessageResource($message->load('user')),
        ]);
    }

    public function markAsRead(Request $request, string $conversationId, string $messageId): JsonResponse
    {
        $user = $request->user();
        
        $message = Message::where('conversation_id', $conversationId)
            ->findOrFail($messageId);

        $this->conversationService->markMessageAsRead($message, $user);

        return response()->json([
            'message' => 'Message marked as read',
        ]);
    }

    public function destroy(Request $request, string $conversationId, string $messageId): JsonResponse
    {
        $user = $request->user();
        
        $message = Message::where('conversation_id', $conversationId)
            ->findOrFail($messageId);

        $this->conversationService->deleteMessage($message, $user);

        return response()->json(null, 204);
    }
}
