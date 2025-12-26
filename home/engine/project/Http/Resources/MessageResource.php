<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'conversation_id' => $this->conversation_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'uuid' => $this->user->uuid,
                    'username' => $this->user->username,
                    'full_name' => $this->user->full_name,
                    'profile_image_url' => $this->user->profile_image_url,
                ];
            }),
            'content' => $this->content,
            'type' => $this->type,
            'attachments' => $this->attachments ?? [],
            'is_read' => $this->isRead(),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'is_from_current_user' => $this->user_id === $request->user()?->id,
        ];
    }
}
