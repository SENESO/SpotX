<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedPost;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\PostResource;

class SavedPostController extends Controller
{
    public function index(Request $request)
    {
        $savedPosts = SavedPost::where('user_id', $request->user()->id)
            ->with(['post' => function ($query) {
                $query->with(['author', 'media', 'mentions', 'hashtags'])
                    ->withCount(['likes', 'reposts', 'quotes', 'replies']);
            }])
            ->orderByDesc('saved_at')
            ->paginate(20);

        $transformedPosts = $savedPosts->map(function ($savedPost) {
            return $savedPost->post;
        });

        return response()->json([
            'data' => PostResource::collection($transformedPosts),
            'pagination' => [
                'current_page' => $savedPosts->currentPage(),
                'next_page_url' => $savedPosts->nextPageUrl(),
                'prev_page_url' => $savedPosts->previousPageUrl(),
            ],
        ]);
    }

    public function save(Request $request, $postId)
    {
        $post = Post::findOrFail($postId);
        
        if ($request->user()->id === $post->user_id) {
            return response()->json([
                'message' => 'You cannot save your own post'
            ], Response::HTTP_BAD_REQUEST);
        }

        $existingSave = SavedPost::where([
            'user_id' => $request->user()->id,
            'post_id' => $postId,
        ])->first();

        if ($existingSave) {
            return response()->json([
                'message' => 'Post already saved'
            ], Response::HTTP_BAD_REQUEST);
        }

        SavedPost::create([
            'user_id' => $request->user()->id,
            'post_id' => $postId,
            'saved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Post saved successfully'
        ], Response::HTTP_CREATED);
    }

    public function unsave(Request $request, $postId)
    {
        $savedPost = SavedPost::where([
            'user_id' => $request->user()->id,
            'post_id' => $postId,
        ])->firstOrFail();

        $savedPost->delete();

        return response()->json([
            'message' => 'Post unsaved successfully'
        ]);
    }

    public function check(Request $request, $postId)
    {
        $isSaved = SavedPost::where([
            'user_id' => $request->user()->id,
            'post_id' => $postId,
        ])->exists();

        return response()->json([
            'saved' => $isSaved,
        ]);
    }
}