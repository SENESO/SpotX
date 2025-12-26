<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $otherParticipant = $this->participants
            ->where('id', '!=', $request->user()?->id)
            ->first();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'subject' => $this->subject,
            'other_participant' => $otherParticipant ? [
                'id' => $otherParticipant->id,
                'uuid' => $otherParticipant->uuid,
                'username' => $otherParticipant->username,
                'full_name' => $otherParticipant->full_name,
                'profile_image_url' => $otherParticipant->profile_image_url,
            ] : null,
            'participants_count' => $this->participants->count(),
            'last_message' => $this->whenLoaded('lastMessage', function () {
                if (!$this->lastMessage) {
                    return null;
                }
                return [
                    'id' => $this->lastMessage->id,
                    'content' => \Str::limit($this->lastMessage->content, 100),
                    'type' => $this->lastMessage->type,
                    'user_id' => $this->lastMessage->user_id,
                    'created_at' => $this->lastMessage->created_at->toIso8601String(),
                ];
            }),
            'last_message_preview' => $this->last_message_preview,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
