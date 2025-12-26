<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'content' => $this->content,
            'media_urls' => $this->media_urls ?? [],
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'likes_count' => $this->likes_count ?? 0,
            'reposts_count' => $this->reposts_count ?? 0,
            'quotes_count' => $this->quotes_count ?? 0,
            'replies_count' => $this->replies_count ?? 0,
            'views_count' => $this->views_count ?? 0,
            'is_liked' => $this->whenPivotLoaded('interaction_likes', function () {
                return $this->pivot->liked ?? false;
            }, $this->is_liked ?? false),
            'is_reposted' => $this->whenPivotLoaded('interaction_reposts', function () {
                return $this->pivot->reposted ?? false;
            }, $this->is_reposted ?? false),
            'is_quoted' => $this->whenPivotLoaded('interaction_quotes', function () {
                return $this->pivot->quoted ?? false;
            }, $this->is_quoted ?? false),
            'visibility' => $this->visibility ?? 'public',
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($this->original_post_uuid && $this->relationLoaded('originalPost')) {
            $data['original_post'] = new PostResource($this->whenLoaded('originalPost'));
        }

        if ($this->relationLoaded('quotedPosts')) {
            $data['quoted_by'] = PostResource::collection($this->quotedPosts);
        }

        return $data;
    }
}
