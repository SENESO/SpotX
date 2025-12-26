<?php

namespace App\Observers;

use App\Models\Interaction;
use App\Models\Post;

class InteractionObserver
{
    public function created(Interaction $interaction): void
    {
        $this->incrementCounter($interaction);
    }

    public function deleted(Interaction $interaction): void
    {
        $this->decrementCounter($interaction);
    }

    protected function incrementCounter(Interaction $interaction): void
    {
        $post = Post::find($interaction->post_id);
        
        if (!$post) {
            return;
        }

        match ($interaction->interaction_type) {
            'like' => $post->increment('likes_count'),
            'repost' => $post->increment('reposts_count'),
            'quote' => $post->increment('quotes_count'),
            default => null,
        };
    }

    protected function decrementCounter(Interaction $interaction): void
    {
        $post = Post::find($interaction->post_id);
        
        if (!$post) {
            return;
        }

        match ($interaction->interaction_type) {
            'like' => $post->decrement('likes_count'),
            'repost' => $post->decrement('reposts_count'),
            'quote' => $post->decrement('quotes_count'),
            default => null,
        };
    }
}
