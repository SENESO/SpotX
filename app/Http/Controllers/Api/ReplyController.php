<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReplyResource;
use App\Models\Post;
use App\Models\Reply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReplyController extends Controller
{
    public function store(Request $request, string $postId): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:500'],
            'parent_reply_id' => ['sometimes', 'exists:replies,id'],
            'media_files' => ['sometimes', 'array', 'max:10'],
            'media_files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,mp4,webm', 'max:102400'],
        ]);

        $user = $request->user();
        $post = Post::findOrFail($postId);

        $reply = Reply::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_reply_id' => $request->get('parent_reply_id'),
            'content' => $request->validated()['content'],
        ]);

        $reply->load(['user']);

        return response()->json([
            'message' => 'Reply created successfully',
            'reply' => new ReplyResource($reply),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $reply = Reply::findOrFail($id);

        if ($reply->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $reply->update([
            'content' => $request->validated()['content'],
        ]);

        $reply->load(['user']);

        return response()->json([
            'message' => 'Reply updated successfully',
            'reply' => new ReplyResource($reply),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $reply = Reply::findOrFail($id);

        if ($reply->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $reply->delete();

        return response()->json(null, 204);
    }

    public function index(Request $request, string $postId): JsonResponse
    {
        $post = Post::findOrFail($postId);
        $limit = min((int) $request->get('per_page', 20), 50);
        $cursor = $request->get('cursor');

        $query = Reply::where('post_id', $postId)
            ->whereNull('parent_reply_id')
            ->with(['user']);

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $replies = $query->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'replies' => ReplyResource::collection($replies->items()),
            'pagination' => [
                'current_page' => $replies->currentPage(),
                'per_page' => $replies->perPage(),
                'total' => $replies->total(),
                'last_page' => $replies->lastPage(),
                'has_more' => $replies->hasMorePages(),
                'next_cursor' => $replies->hasMorePages() ? $replies->items()[count($replies->items()) - 1]->id : null,
            ],
            'count' => $post->replies_count,
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $reply = Reply::with(['user', 'post', 'parentReply.user', 'childReplies.user'])->findOrFail($id);

        return response()->json([
            'reply' => new ReplyResource($reply),
        ]);
    }
}
