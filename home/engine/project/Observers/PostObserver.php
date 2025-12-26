<?php

namespace App\Observers;

use App\Models\Post;

class PostObserver
{
    public function created(Post $post): void
    {
        $post->update([
            'likes_count' => 0,
            'reposts_count' => 0,
            'quotes_count' => 0,
            'replies_count' => 0,
            'views_count' => 0,
        ]);
    }

    public function deleting(Post $post): void
    {
        $post->media()->delete();
        $post->mentions()->delete();
        
        $post->interactions()->delete();
    }
}
