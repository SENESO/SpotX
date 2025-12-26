<?php

namespace App\Services;

use App\Models\Interaction;
use App\Models\Post;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class InteractionService
{
    public function like(User $user, Post $post): Interaction
    {
        return DB::transaction(function () use ($user, $post) {
            $interaction = Interaction::firstOrCreate([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'interaction_type' => 'like',
            ]);

            $post->increment('likes_count');

            if ($post->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $post->user_id,
                    'actor_id' => $user->id,
                    'type' => 'like',
                    'related_post_id' => $post->id,
                ]);
            }

            return $interaction;
        });
    }

    public function unlike(User $user, Post $post): void
    {
        DB::transaction(function () use ($user, $post) {
            $deleted = Interaction::where([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'interaction_type' => 'like',
            ])->delete();

            if ($deleted) {
                $post->decrement('likes_count');
            }
        });
    }

    public function repost(User $user, Post $originalPost): Interaction
    {
        return DB::transaction(function () use ($user, $originalPost) {
            $interaction = Interaction::firstOrCreate([
                'user_id' => $user->id,
                'post_id' => $originalPost->id,
                'interaction_type' => 'repost',
            ]);

            $originalPost->increment('reposts_count');

            if ($originalPost->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $originalPost->user_id,
                    'actor_id' => $user->id,
                    'type' => 'repost',
                    'related_post_id' => $originalPost->id,
                ]);
            }

            return $interaction;
        });
    }

    public function unrepost(User $user, Post $post): void
    {
        DB::transaction(function () use ($user, $post) {
            $deleted = Interaction::where([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'interaction_type' => 'repost',
            ])->delete();

            if ($deleted) {
                $post->decrement('reposts_count');
            }
        });
    }

    public function quote(User $user, Post $originalPost, string $content): Post
    {
        return DB::transaction(function () use ($user, $originalPost, $content) {
            $quotePost = Post::create([
                'user_id' => $user->id,
                'content' => $content,
                'original_post_uuid' => $originalPost->uuid,
                'visibility' => 'public',
            ]);

            $user->increment('posts_count');

            Interaction::create([
                'user_id' => $user->id,
                'post_id' => $originalPost->id,
                'interaction_type' => 'quote',
            ]);

            $originalPost->increment('quotes_count');

            if ($originalPost->user_id !== $user->id) {
                Notification::create([
                    'user_id' => $originalPost->user_id,
                    'actor_id' => $user->id,
                    'type' => 'quote',
                    'related_post_id' => $originalPost->id,
                ]);
            }

            return $quotePost->fresh();
        });
    }

    public function unquote(User $user, Post $originalPost): void
    {
        DB::transaction(function () use ($user, $originalPost) {
            $deleted = Interaction::where([
                'user_id' => $user->id,
                'post_id' => $originalPost->id,
                'interaction_type' => 'quote',
            ])->delete();

            if ($deleted) {
                $originalPost->decrement('quotes_count');
            }
        });
    }

    public function hasInteracted(User $user, Post $post, string $type): bool
    {
        return Interaction::where([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'interaction_type' => $type,
        ])->exists();
    }

    public function getPostInteractions(Post $post, string $type, int $limit = 20, int $afterId = 0)
    {
        return Interaction::where('post_id', $post->id)
            ->where('interaction_type', $type)
            ->where('id', '>', $afterId)
            ->with('user')
            ->limit($limit)
            ->get();
    }
}
