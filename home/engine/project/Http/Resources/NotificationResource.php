<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'is_read' => $this->is_read,
            'actor' => [
                'id' => $this->actor->id,
                'uuid' => $this->actor->uuid,
                'username' => $this->actor->username,
                'full_name' => $this->actor->full_name,
                'profile_image_url' => $this->actor->profile_image_url,
            ],
            'related_post' => $this->whenLoaded('relatedPost', function () {
                if (!$this->relatedPost) {
                    return null;
                }
                return [
                    'id' => $this->relatedPost->id,
                    'uuid' => $this->relatedPost->uuid,
                    'content' => \Str::limit($this->relatedPost->content, 100),
                ];
            }),
            'action_url' => $this->action_url,
            'preview_text' => $this->preview_text,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
