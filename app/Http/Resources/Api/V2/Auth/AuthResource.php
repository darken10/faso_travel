<?php

namespace App\Http\Resources\Api\V2\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success'       => $this->resource['success'],
            'user'          => $this->resource['user'],
            'token'         => $this->resource['token'],
            'refresh_token' => $this->resource['refresh_token'],
        ];
    }
}
