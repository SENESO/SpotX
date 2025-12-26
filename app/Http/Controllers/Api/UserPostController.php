<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use App\Services\FeedService;
use App\Services\InteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPostController extends Controller
{
    protected FeedService $feedService;
    protected InteractionService $interactionService;

    public function __construct(
        FeedService $feedService,
        InteractionService $interactionService
    ) {
        $this->feedService = $feedService;
        $this->interactionService = $interactionService;
    }

    public function index(Request $request, string $id): JsonResponse
    {
        $viewer = $request->user();
        $targetUser = User::findOrFail($id);
        
        $includeReposts = $request->get('include_reposts', false);
        $perPage = min((int) $request->get('per_page', 20), 50);
        $cursor = $request->get('cursor');

        $posts = $this->feedService->getUserPosts(
            $targetUser,
            $viewer,
            $includeReposts,
            $perPage,
            $cursor
        );

        return response()->json([
            'posts' => PostResource::collection($posts->items()),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
                'has_more' => $posts->hasMorePages(),
                'next_cursor' => $posts->hasMorePages() ? $posts->items()[count($posts->items()) - 1]->id : null,
            ],
        ]);
    }

    public function media(Request $request, string $id): JsonResponse
    {
        $viewer = $request->user();
        $targetUser = User::findOrFail($id);
        
        $perPage = min((int) $request->get('per_page', 20), 50);
        $cursor = $request->get('cursor');

        $posts = $this->feedService->getUserMediaPosts(
            $targetUser,
            $viewer,
            $perPage,
            $cursor
        );

        return response()->json([
            'posts' => PostResource::collection($posts->items()),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
                'has_more' => $posts->hasMorePages(),
                'next_cursor' => $posts->hasMorePages() ? $posts->items()[count($posts->items()) - 1]->id : null,
            ],
        ]);
    }

    public function likes(Request $request, string $id): JsonResponse
    {
        $viewer = $request->user();
        $targetUser = User::findOrFail($id);
        
        $isOwner = $targetUser->id === $viewer->id;
        $isFollowing = $viewer->isFollowing($targetUser);

        if ($targetUser->is_private && !$isOwner && !$isFollowing) {
            return response()->json([
                'message' => 'This account is private',
                'posts' => [],
                'pagination' => [],
            ]);
        }

        $limit = min((int) $request->get('per_page', 20), 50);
        $cursor = $request->get('cursor');

        $query = Post::query()
            ->with(['user', 'media', 'originalPost.user'])
            ->whereHas('interactions', function ($q) use ($targetUser) {
                $q->where('user_id', $targetUser->id)
                  ->where('interaction_type', 'like');
            });

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $posts = $query->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'posts' => PostResource::collection($posts->items()),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
                'has_more' => $posts->hasMorePages(),
                'next_cursor' => $posts->hasMorePages() ? $posts->items()[count($posts->items()) - 1]->id : null,
            ],
        ]);
    }
}
