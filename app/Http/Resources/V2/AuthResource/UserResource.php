<?php

namespace App\Http\Resources\V2\AuthResource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->profile_photo_url,
            'role' => $this->role->name,
            'compagnie' => optional($this->compagnie)->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'sexe' => $this->sexe,
            'statut' => $this->statut,
            'numero' => $this->numero,
            'numero_identifiant' => $this->numero_identifiant,
            'email_verified_at' => $this->email_verified_at,
            'phone_verified_at' => $this->phone_verified_at,
            'is_verified' => $this->resource->isVerified(),
            'loyalty_points' => (int) $this->loyalty_points,
            'loyalty_lifetime_points' => (int) $this->loyalty_lifetime_points,
            'loyalty_tier' => \App\Enums\LoyaltyTier::pour((int) $this->loyalty_lifetime_points)->value,
            'two_factor_confirmed_at' => $this->two_factor_confirmed_at,
            'current_team_id' => $this->current_team_id,
            'profile_photo_path' => $this->profile_photo_path,
            'profile_photo_url' => $this->profile_photo_url,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
        ];
    }
}
