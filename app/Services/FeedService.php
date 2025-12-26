<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FeedService
{
    protected int $defaultPerPage = 20;
    protected int $maxPerPage = 50;

    public function getChronologicalFeed(User $user, int $perPage = 20, ?string $cursor = null): LengthAwarePaginator
    {
        $perPage = min($perPage, $this->maxPerPage);
        
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
            ->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                  ->orWhere(function ($sub) use ($user) {
                      $sub->where('visibility', 'followers_only')
                          ->whereIn('user_id', $user->following()->select('following_id'));
                  })
                  ->orWhere('user_id', $user->id);
            })
            ->where('user_id', '!=', $user->id);

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getPersonalizedFeed(User $user, int $perPage = 20, ?string $cursor = null): LengthAwarePaginator
    {
        $perPage = min($perPage, $this->maxPerPage);
        
        $followingIds = $user->following()->select('following_id')->get()->pluck('id');

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
            ->where('user_id', '!=', $user->id)
            ->where(function ($q) use ($user, $followingIds) {
                $q->where('visibility', 'public')
                  ->orWhere(function ($sub) use ($user, $followingIds) {
                      $sub->where('visibility', 'followers_only')
                          ->whereIn('user_id', $followingIds);
                  });
            });

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        $recentThreshold = now()->subHours(24);
        $highEngagementThreshold = now()->subDays(7);

        return $query
            ->select('posts.*')
            ->addSelect([
                'engagement_score' => Post::raw('
                    (CASE 
                        WHEN created_at >= ? THEN likes_count * 3 + reposts_count * 4 + quotes_count * 5 + views_count * 0.1
                        ELSE likes_count + reposts_count * 2 + quotes_count * 3 + views_count * 0.05
                    END)
                ', [$recentThreshold]),
                'following_score' => Post::raw('
                    CASE WHEN user_id IN (?) THEN 5 ELSE 0 END
                ', [$followingIds->implode(',')]),
            ])
            ->orderByRaw('(engagement_score + following_score) DESC, created_at DESC')
            ->paginate($perPage);
    }

    public function getFeed(User $user, string $algorithm = 'chronological', int $perPage = 20, ?string $cursor = null): LengthAwarePaginator
    {
        if ($algorithm === 'personalized') {
            return $this->getPersonalizedFeed($user, $perPage, $cursor);
        }

        return $this->getChronologicalFeed($user, $perPage, $cursor);
    }

    public function getUserPosts(User $targetUser, User $viewer, bool $includeReposts = false, int $perPage = 20, ?string $cursor = null)
    {
        $perPage = min($perPage, $this->maxPerPage);
        
        $isOwner = $targetUser->id === $viewer->id;
        $isFollowing = $viewer->isFollowing($targetUser);

        $query = Post::query()
            ->with(['user', 'media', 'originalPost.user'])
            ->where('user_id', $targetUser->id);

        if (!$isOwner) {
            if ($targetUser->is_private && !$isFollowing) {
                return Post::query()->whereRaw('1 = 0')->paginate($perPage);
            }

            $query->where(function ($q) use ($viewer, $isFollowing) {
                $q->where('visibility', 'public')
                  ->orWhere(function ($sub) use ($viewer, $isFollowing) {
                      $sub->where('visibility', 'followers_only')
                          ->where(function ($inner) use ($viewer, $isFollowing) {
                               $inner->where('user_id', $viewer->id)
                                     ->orWhere(function ($f) use ($isFollowing) {
                                         $f->where('user_id', '!=', $viewer->id)
                                           ->where('user_id', $isFollowing ? '!= 0' : '= 0');
                                     });
                           });
                  });
            });
        }

        if (!$includeReposts) {
            $query->whereNull('original_post_uuid');
        }

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getUserMediaPosts(User $targetUser, User $viewer, int $perPage = 20, ?string $cursor = null)
    {
        $perPage = min($perPage, $this->maxPerPage);
        
        $isOwner = $targetUser->id === $viewer->id;
        $isFollowing = $viewer->isFollowing($targetUser);

        $query = Post::query()
            ->with(['user', 'media'])
            ->where('user_id', $targetUser->id)
            ->has('media');

        if (!$isOwner) {
            if ($targetUser->is_private && !$isFollowing) {
                return Post::query()->whereRaw('1 = 0')->paginate($perPage);
            }

            $query->where(function ($q) {
                $q->where('visibility', 'public')
                  ->orWhere('user_id', $viewer->id);
            });
        }

        if ($cursor) {
            $query->where('id', '<', $cursor);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
