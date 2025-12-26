<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InteractionResource;
use App\Models\Post;
use App\Services\InteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    protected InteractionService $interactionService;

    public function __construct(InteractionService $interactionService)
    {
        $this->interactionService = $interactionService;
    }

    public function store(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        $interaction = $this->interactionService->like($user, $post);

        return response()->json([
            'message' => 'Post liked successfully',
            'interaction' => new InteractionResource($interaction),
        ], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        $this->interactionService->unlike($user, $post);

        return response()->json(null, 204);
    }

    public function index(Request $request, string $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $limit = min((int) $request->get('per_page', 20), 50);
        $afterId = (int) $request->get('after_id', 0);

        $interactions = $this->interactionService->getPostInteractions($post, 'like', $limit, $afterId);

        return response()->json([
            'users' => $interactions->map(function ($interaction) {
                return [
                    'user' => [
                        'id' => $interaction->user->id,
                        'uuid' => $interaction->user->uuid,
                        'username' => $interaction->user->username,
                        'full_name' => $interaction->user->full_name,
                        'profile_image_url' => $interaction->user->profile_image_url,
                    ],
                    'liked_at' => $interaction->created_at->toIso8601String(),
                ];
            }),
            'count' => $post->likes_count,
        ]);
    }
}
