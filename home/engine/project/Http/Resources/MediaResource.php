<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'url' => $this->media_url,
            'thumbnail_url' => $this->thumbnail_url,
            'type' => $this->media_type,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
        ];
    }
}
