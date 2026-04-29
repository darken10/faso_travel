<?php

namespace App\Http\Resources\ApiV2;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = collect($this->images_uri ?? [])
            ->map(fn($path) => $path ? url(Storage::url($path)) : null)
            ->filter()
            ->values()
            ->toArray();

        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'summary'        => Str::limit(strip_tags($this->content ?? ''), 120),
            'content'        => $this->content,
            'category'       => $this->category?->name,
            'images'         => $images,
            'like_count'     => $this->likes->count(),
            'comments_count' => $this->comments->count(),
            'i_liked'        => auth()->check()
                ? $this->likes->contains('user_id', auth()->id())
                : false,
            'tags'           => $this->tags->pluck('name'),
            'user'           => [
                'id'    => $this->user?->id,
                'name'  => $this->user?->name,
                'photo' => $this->user?->profile_photo_url,
            ],
            'compagnie'      => $this->user?->compagnie ? [
                'name' => $this->user->compagnie->name,
                'logo' => $this->user->compagnie->logo,
            ] : null,
            'comments'       => $this->comments->map(fn($comment) => [
                'id'         => $comment->id,
                'content'    => $comment->message,
                'created_at' => $comment->created_at?->toIso8601String(),
                'author'     => [
                    'id'     => $comment->user?->id,
                    'name'   => $comment->user?->name,
                    'avatar' => $comment->user?->profile_photo_url,
                ],
            ]),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
