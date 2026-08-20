<?php

namespace App\Http\Resources\ApiV2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Gare telle que consommée par l'application voyageur : identité, ville et
 * coordonnées, de quoi ouvrir directement l'itinéraire sur la carte.
 *
 * @mixin \App\Models\Compagnie\Gare
 */
class StationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $lat = (float) ($this->lat ?? 0);
        $lng = (float) ($this->lng ?? 0);

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'city'        => $this->ville?->name,
            'region'      => $this->ville?->region?->name,
            'lat'         => $lat,
            'lng'         => $lng,
            'has_coords'  => $lat !== 0.0 || $lng !== 0.0,
            'is_default'  => (bool) $this->is_default,
            'company'     => $this->whenLoaded('compagnie', fn () => $this->compagnie ? [
                'id'    => $this->compagnie->id,
                'name'  => $this->compagnie->name,
                'sigle' => $this->compagnie->sigle,
                'logo'  => $this->compagnie->logo_uri,
            ] : null),
        ];
    }
}
