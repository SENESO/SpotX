<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuotePostRequest;
use App\Http\Resources\InteractionResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\InteractionService;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
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

    public function store(QuotePostRequest $request, string $id): JsonResponse
    {
        $user = $request->user();
        $originalPost = Post::findOrFail($id);

        $content = $request->validated()['content'];
        
        $quotePost = $this->interactionService->quote($user, $originalPost, $content);
        
        $quotePost->load(['user', 'media', 'originalPost.user']);

        return response()->json([
            'message' => 'Quote post created successfully',
            'post' => new PostResource($quotePost),
        ], 201);
    }

    public function index(Request $request, string $id): JsonResponse
    {
        $originalPost = Post::findOrFail($id);
        
        $limit = min((int) $request->get('per_page', 20), 50);
        $cursor = $request->get('cursor');

        $query = Post::where('original_post_uuid', $originalPost->uuid)
            ->with(['user', 'media']);

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $quotes = $query->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'posts' => PostResource::collection($quotes->items()),
            'pagination' => [
                'current_page' => $quotes->currentPage(),
                'per_page' => $quotes->perPage(),
                'total' => $quotes->total(),
                'last_page' => $quotes->lastPage(),
                'has_more' => $quotes->hasMorePages(),
                'next_cursor' => $quotes->hasMorePages() ? $quotes->items()[count($quotes->items()) - 1]->id : null,
            ],
            'count' => $originalPost->quotes_count,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $originalPost = Post::findOrFail($id);

        $this->interactionService->unquote($user, $originalPost);

        return response()->json(null, 204);
    }
}
