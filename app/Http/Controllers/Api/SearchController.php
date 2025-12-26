<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['sometimes', 'string', 'in:all,posts,users,hashtags'],
        ]);

        $query = $request->get('q');
        $type = $request->get('type', 'all');
        $user = $request->user();

        $results = $this->searchService->search($query, $type, $user);

        $formattedResults = [];
        
        if (isset($results['posts'])) {
            $formattedResults['posts'] = [
                'data' => PostResource::collection($results['posts']['data']->items()),
                'pagination' => [
                    'current_page' => $results['posts']['data']->currentPage(),
                    'per_page' => $results['posts']['data']->perPage(),
                    'total' => $results['posts']['data']->total(),
                    'last_page' => $results['posts']['data']->lastPage(),
                    'has_more' => $results['posts']['data']->hasMorePages(),
                ],
            ];
        }

        if (isset($results['users'])) {
            $formattedResults['users'] = [
                'data' => $results['users']['data'],
            ];
        }

        if (isset($results['hashtags'])) {
            $formattedResults['hashtags'] = [
                'data' => $results['hashtags']['data'],
            ];
        }

        return response()->json([
            'query' => $query,
            'type' => $type,
            'results' => $formattedResults,
        ]);
    }

    public function posts(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $query = $request->get('q');
        $user = $request->user();

        $posts = $this->searchService->searchPosts($query, $user);

        return response()->json([
            'query' => $query,
            'posts' => PostResource::collection($posts->items()),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
                'has_more' => $posts->hasMorePages(),
            ],
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $query = $request->get('q');
        $user = $request->user();

        $users = $this->searchService->searchUsers($query, $user);

        return response()->json([
            'query' => $query,
            'users' => $users,
        ]);
    }

    public function hashtags(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $query = $request->get('q');

        $hashtags = $this->searchService->searchHashtags($query);

        return response()->json([
            'query' => $query,
            'hashtags' => $hashtags,
        ]);
    }
}
