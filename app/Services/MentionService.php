<?php

namespace App\Services;

use App\Models\User;
use App\Models\Post;
use App\Models\Mention;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MentionService
{
    public function parseAndCreateMentions(Post $post): Collection
    {
        $mentions = $this->extractMentions($post->content);
        $createdMentions = collect();

        foreach ($mentions as $username) {
            $user = User::where('username', $username)->first();
            
            if ($user && $user->id !== $post->user_id) {
                $mention = Mention::firstOrCreate([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);

                if ($mention->wasRecentlyCreated) {
                    $createdMentions->push($mention);
                    
                    Notification::create([
                        'user_id' => $user->id,
                        'actor_id' => $post->user_id,
                        'type' => 'mention',
                        'related_post_id' => $post->id,
                    ]);
                }
            }
        }

        return $createdMentions;
    }

    public function extractMentions(string $content): Collection
    {
        preg_match_all('/@(\w+)/u', $content, $matches);
        
        return collect($matches[1] ?? [])->unique();
    }

    public function getSuggestions(User $user, string $query, int $limit = 10): Collection
    {
        return User::where('id', '!=', $user->id)
            ->where('username', 'like', "{$query}%")
            ->orWhere('full_name', 'like', "%{$query}%")
            ->limit($limit)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'username' => $u->username,
                    'full_name' => $u->full_name,
                    'profile_image_url' => $u->profile_image_url,
                ];
            });
    }

    public function getUserMentions(User $user, int $limit = 20, int $afterId = 0)
    {
        return Mention::where('user_id', $user->id)
            ->where('id', '>', $afterId)
            ->with(['post.user', 'post.media'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
