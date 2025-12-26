<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\Interaction;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PostService
{
    protected InteractionService $interactionService;

    public function __construct(InteractionService $interactionService)
    {
        $this->interactionService = $interactionService;
    }

    public function createPost(array $data, User $user): Post
    {
        return DB::transaction(function () use ($data, $user) {
            $post = Post::create([
                'user_id' => $user->id,
                'content' => $data['content'],
                'media_urls' => $data['media_urls'] ?? [],
                'visibility' => $data['visibility'] ?? 'public',
            ]);

            $user->increment('posts_count');

            if (!empty($data['media_ids'])) {
                PostMedia::whereIn('id', $data['media_ids'])
                    ->whereNull('post_id')
                    ->update(['post_id' => $post->id]);
            }

            if (!empty($data['original_post_uuid'])) {
                $post->update(['original_post_uuid' => $data['original_post_uuid']]);
            }

            return $post->fresh();
        });
    }

    public function updatePost(Post $post, array $data, User $user): Post
    {
        if ($post->user_id !== $user->id) {
            throw new \UnauthorizedException('You can only edit your own posts');
        }

        $updateData = [];
        
        if (array_key_exists('content', $data)) {
            $updateData['content'] = $data['content'];
        }
        
        if (array_key_exists('media_urls', $data)) {
            $updateData['media_urls'] = $data['media_urls'];
        }
        
        if (array_key_exists('visibility', $data)) {
            $updateData['visibility'] = $data['visibility'];
        }

        $post->update($updateData);

        return $post->fresh();
    }

    public function deletePost(Post $post, User $user): void
    {
        if ($post->user_id !== $user->id) {
            throw new \UnauthorizedException('You can only delete your own posts');
        }

        DB::transaction(function () use ($post, $user) {
            $post->media()->delete();
            $post->mentions()->delete();
            
            $post->delete();

            $user->decrement('posts_count');
        });
    }

    public function getPostWithDetails(Post $post, ?User $viewer = null): Post
    {
        $post->load(['user', 'media', 'originalPost.user']);

        if ($viewer) {
            $post->setRelation('interactions', null);
            $post->is_liked = $this->interactionService->hasInteracted($viewer, $post, 'like');
            $post->is_reposted = $this->interactionService->hasInteracted($viewer, $post, 'repost');
            $post->is_quoted = $this->interactionService->hasInteracted($viewer, $post, 'quote');
        } else {
            $post->is_liked = false;
            $post->is_reposted = false;
            $post->is_quoted = false;
        }

        return $post;
    }

    public function incrementViews(Post $post): void
    {
        $post->increment('views_count');
    }
}
