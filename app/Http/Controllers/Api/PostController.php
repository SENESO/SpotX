<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\MediaService;
use App\Services\MentionService;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    protected PostService $postService;
    protected MediaService $mediaService;
    protected MentionService $mentionService;

    public function __construct(
        PostService $postService,
        MediaService $mediaService,
        MentionService $mentionService
    ) {
        $this->postService = $postService;
        $this->mediaService = $mediaService;
        $this->mentionService = $mentionService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->get('per_page', 20), 50);
        $cursor = $request->get('cursor');

        $query = Post::query()
            ->with(['user', 'media', 'originalPost.user'])
            ->whereDoesntHave('user.blockedBy', function ($q) use ($user) {
                $q->where('blocker_id', $user->id);
            })
            ->whereDoesntHave('user.blocks', function ($q) use ($user) {
                $q->where('blocked_id', $user->id);
            })
            ->whereHas('user', function ($q) {
                $q->where('is_suspended', false);
            })
            ->where('user_id', '!=', $user->id);

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $posts = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

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

    public function store(CreatePostRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $data = $request->validated();
        
        if ($request->hasFile('media_files')) {
            $mediaItems = $this->mediaService->uploadMultiple(
                $request->file('media_files')
            );
            $data['media_urls'] = $this->mediaService->getMediaUrls($mediaItems);
            $data['media_ids'] = array_column($mediaItems->toArray(), 'id');
        }

        $post = $this->postService->createPost($data, $user);
        
        $this->mentionService->parseAndCreateMentions($post);

        $post->load(['user', 'media']);

        return response()->json([
            'message' => 'Post created successfully',
            'post' => new PostResource($post),
        ], 201);
    }

    public function update(UpdatePostRequest $request, string $id): JsonResponse
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        $data = $request->validated();

        if ($request->hasFile('media_files')) {
            $mediaItems = $this->mediaService->uploadMultiple(
                $request->file('media_files')
            );
            $existingUrls = $post->media_urls ?? [];
            $newUrls = $this->mediaService->getMediaUrls($mediaItems);
            $data['media_urls'] = array_merge($existingUrls, $newUrls);
        }

        $updatedPost = $this->postService->updatePost($post, $data, $user);
        
        if (array_key_exists('content', $data)) {
            $this->mentionService->parseAndCreateMentions($updatedPost);
        }

        $updatedPost->load(['user', 'media']);

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => new PostResource($updatedPost),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        $this->postService->deletePost($post, $user);

        return response()->json(null, 204);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $post = Post::with(['user', 'media', 'originalPost.user'])->findOrFail($id);

        if (!$post->isVisibleTo($user)) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        $this->postService->incrementViews($post);

        $post->load(['user', 'media', 'originalPost.user']);

        return response()->json([
            'post' => new PostResource($post),
        ]);
    }
}
