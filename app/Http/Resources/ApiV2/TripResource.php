<?php

namespace App\Http\Resources\ApiV2;

use Carbon\Carbon;
use App\Enums\TypeTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $voyage = $this->voyage;
        $trajet = $voyage->trajet;
        $depart = $trajet->depart;
        $arriver = $trajet->arriver;
        $compagnie = $voyage->compagnie;
        // Calculate available seats
        $totalSeats = $this->nb_place ?: ($this->care?->number_place ?? 50);
        $occupiedSeats = \App\Models\Ticket\Ticket::where('voyage_instance_id', $this->id)
            ->where('statut', '!=', 'annuler')
            ->count();
        $availableSeats = max(0, $totalSeats - $occupiedSeats);

        // Calculate estimated arrival time
        $date = Carbon::parse($this->date)->format('Y-m-d');
        $heure = Carbon::parse($voyage->heure)->format('H:i:s');

        $departureTime = Carbon::parse("$date $heure");
        $duration = $voyage->temps ?? ''; // Default 2 hours if not set
        $arrivalTime = (clone $departureTime)->addMinutes(
            \Carbon\Carbon::parse($duration)->diffInMinutes(\Carbon\Carbon::today())
        );

        return [
            'id' => $this->id,
            'departure' => [
                'city'    => $depart->name,
                'station' => $this->gareDepart()->name,
                'time'    => $departureTime->format('Y-m-d H:i:s'),
                'lat'     => (float) ($this->gareDepart()->lat ?? 0),
                'lng'     => (float) ($this->gareDepart()->lng ?? 0),
            ],
            'arrival' => [
                'city'    => $arriver->name,
                'station' => $this->gareArrive()->name,
                'time'    => $arrivalTime->format('Y-m-d H:i:s'),
                'lat'     => (float) ($this->gareArrive()->lat ?? 0),
                'lng'     => (float) ($this->gareArrive()->lng ?? 0),
            ],
            'company' => [
                'id' => $compagnie->id,
                'name' => $compagnie->name,
                'logo' => $compagnie->logo,
                'rating' => $compagnie->rating ?? 4.5
            ],
            'price' => (float) $voyage->prix,
            'care' => $this->care,
            'alle_simple' => $voyage->getPrix(TypeTicket::AllerSimple),
            'aller_retour'=> $voyage->getPrix(TypeTicket::AllerRetour),
            'devise' => [
                'name' => $compagnie->getDevise(),
                'position' => $compagnie->getDevisePosition(),
                'price_to_usd' => $compagnie->getDevisePriceToUSD(),
            ],
            'chauffer' => $this->chauffer,
            'duration' => $duration,
            'available_seats' => $availableSeats,
            'vehicle_type' => 'bus',
            'classe' => $this->classe()->name,
            'conforts' => $this->conforts()->map(function ($confort) {
                return [
                    'title' => $confort->title,
                    'description' => $confort->description, 
                ];
            }),
        ];
    }
}
