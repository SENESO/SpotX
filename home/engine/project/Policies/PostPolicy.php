<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Post $post): bool
    {
        return $post->isVisibleTo($user);
    }

    public function update(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }

    public function like(User $user, Post $post): bool
    {
        return $post->user_id !== $user->id;
    }

    public function repost(User $user, Post $post): bool
    {
        return $post->user_id !== $user->id;
    }

    public function quote(User $user, Post $post): bool
    {
        return $post->user_id !== $user->id;
    }
}
