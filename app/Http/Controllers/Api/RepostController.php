<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InteractionResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\InteractionService;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepostController extends Controller
{
    protected InteractionService $interactionService;
    protected PostService $postService;

    public function __construct(
        InteractionService $interactionService,
        PostService $postService
    ) {
        $this->interactionService = $interactionService;
        $this->postService = $postService;
    }

    public function store(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        if ($post->user_id === $user->id) {
            return response()->json([
                'message' => 'You cannot repost your own post',
            ], 400);
        }

        $interaction = $this->interactionService->repost($user, $post);

        return response()->json([
            'message' => 'Post reposted successfully',
            'interaction' => new InteractionResource($interaction),
        ], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        $this->interactionService->unrepost($user, $post);

        return response()->json(null, 204);
    }

    public function index(Request $request, string $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $limit = min((int) $request->get('per_page', 20), 50);
        $afterId = (int) $request->get('after_id', 0);

        $interactions = $this->interactionService->getPostInteractions($post, 'repost', $limit, $afterId);

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
                    'reposted_at' => $interaction->created_at->toIso8601String(),
                ];
            }),
            'count' => $post->reposts_count,
        ]);
    }
}
