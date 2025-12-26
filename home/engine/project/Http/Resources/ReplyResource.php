<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'post_id' => $this->post_id,
            'parent_reply_id' => $this->parent_reply_id,
            'parent_reply' => new ReplyResource($this->whenLoaded('parentReply')),
            'content' => $this->content,
            'media_urls' => $this->media_urls ?? [],
            'child_replies_count' => $this->whenLoaded('childReplies', fn() => $this->childReplies->count()),
            'child_replies' => ReplyResource::collection($this->whenLoaded('childReplies')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
