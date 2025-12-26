<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    protected FeedService $feedService;

    public function __construct(FeedService $feedService)
    {
        $this->feedService = $feedService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $algorithm = $request->get('algorithm', 'chronological');
        $perPage = min((int) $request->get('per_page', 20), 50);
        $cursor = $request->get('cursor');

        $posts = $this->feedService->getFeed($user, $algorithm, $perPage, $cursor);

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
            'algorithm' => $algorithm,
        ]);
    }

    public function personalized(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $perPage = min((int) $request->get('per_page', 20), 50);
        $cursor = $request->get('cursor');

        $posts = $this->feedService->getPersonalizedFeed($user, $perPage, $cursor);

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

    public function chronological(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $perPage = min((int) $request->get('per_page', 20), 50);
        $cursor = $request->get('cursor');

        $posts = $this->feedService->getChronologicalFeed($user, $perPage, $cursor);

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
