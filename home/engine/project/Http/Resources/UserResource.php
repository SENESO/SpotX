<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'bio' => $this->bio,
            'profile_image_url' => $this->profile_image_url,
            'header_image_url' => $this->header_image_url,
            'followers_count' => $this->followers_count ?? 0,
            'following_count' => $this->following_count ?? 0,
            'posts_count' => $this->posts_count ?? 0,
            'is_verified' => $this->is_verified ?? false,
            'is_private' => $this->is_private ?? false,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
