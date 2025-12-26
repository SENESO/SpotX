<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MentionController extends Controller
{
    protected MentionService $mentionService;

    public function __construct(MentionService $mentionService)
    {
        $this->mentionService = $mentionService;
    }

    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        $query = $request->get('q');
        $limit = min((int) $request->get('limit', 10), 20);

        $suggestions = $this->mentionService->getSuggestions($user, $query, $limit);

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min((int) $request->get('per_page', 20), 50);
        $afterId = (int) $request->get('after_id', 0);

        $mentions = $this->mentionService->getUserMentions($user, $limit, $afterId);

        return response()->json([
            'mentions' => $mentions->map(function ($mention) {
                return [
                    'id' => $mention->id,
                    'post' => [
                        'id' => $mention->post->id,
                        'uuid' => $mention->post->uuid,
                        'content' => $mention->post->content,
                        'user' => [
                            'id' => $mention->post->user->id,
                            'username' => $mention->post->user->username,
                            'full_name' => $mention->post->user->full_name,
                            'profile_image_url' => $mention->post->user->profile_image_url,
                        ],
                    ],
                    'created_at' => $mention->created_at->toIso8601String(),
                ];
            }),
        ]);
    }
}
