<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'content'    => $this->message,
            'created_at' => $this->created_at?->toIso8601String(),
            'author'     => [
                'id'     => $this->user?->id,
                'name'   => $this->user?->name,
                'avatar' => $this->user?->profile_photo_url,
            ],
        ];
    }
}
