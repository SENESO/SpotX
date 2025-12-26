<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SearchService
{
    public function searchPosts(string $query, User $viewer, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        if (strlen($query) < 2) {
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage
            );
        }

        $posts = Post::query()
            ->with(['user', 'media', 'originalPost.user'])
            ->whereDoesntHave('user.blockedBy', function ($q) use ($viewer) {
                $q->where('blocker_id', $viewer->id);
            })
            ->whereDoesntHave('user.blocks', function ($q) use ($viewer) {
                $q->where('blocked_id', $viewer->id);
            })
            ->whereHas('user', function ($q) {
                $q->where('is_suspended', false);
            })
            ->where(function ($q) use ($viewer) {
                $q->where('visibility', 'public')
                  ->orWhere('user_id', $viewer->id);
            })
            ->where(function ($q) use ($query) {
                $q->where('content', 'like', "%{$query}%")
                  ->orWhereRaw('MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)', [$query]);
            })
            ->orderByRaw('
                CASE 
                    WHEN content LIKE ? THEN 0
                    WHEN content LIKE ? THEN 1
                    ELSE 2
                END
            ', ["{$query}%", "%{$query}%"])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $posts;
    }

    public function searchUsers(string $query, User $viewer, int $limit = 10): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }

        return User::where('id', '!=', $viewer->id)
            ->where('is_suspended', false)
            ->where(function ($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                  ->orWhere('full_name', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->map(function ($user) use ($viewer) {
                return [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'profile_image_url' => $user->profile_image_url,
                    'is_verified' => $user->is_verified,
                    'is_following' => $viewer->isFollowing($user),
                ];
            });
    }

    public function searchHashtags(string $query, int $limit = 10): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }

        $hashtags = Post::query()
            ->selectRaw('SUBSTRING_INDEX(SUBSTRING_INDEX(content, "#", -1), " ", 1) as tag')
            ->where('content', 'like', "%#{$query}%")
            ->whereNotNull('content')
            ->distinct()
            ->limit($limit)
            ->pluck('tag')
            ->map(function ($tag) {
                return [
                    'tag' => strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $tag)),
                    'count' => Post::where('content', 'like', "%#{$tag}%")->count(),
                ];
            })
            ->filter(function ($item) {
                return !empty($item['tag']);
            })
            ->values();

        return $hashtags;
    }

    public function search(string $query, string $type = 'all', User $viewer = null): array
    {
        if (!$viewer) {
            return [];
        }

        $results = [];

        if ($type === 'all' || $type === 'posts') {
            $results['posts'] = [
                'data' => $this->searchPosts($query, $viewer, 20),
                'total' => 0,
            ];
        }

        if ($type === 'all' || $type === 'users') {
            $results['users'] = [
                'data' => $this->searchUsers($query, $viewer),
                'total' => 0,
            ];
        }

        if ($type === 'all' || $type === 'hashtags') {
            $results['hashtags'] = [
                'data' => $this->searchHashtags($query),
                'total' => 0,
            ];
        }

        return $results;
    }
}
