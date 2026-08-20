<?php

namespace App\Http\Resources\ApiV2;

use App\Enums\StatutTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un voyage passé ou à venir, tel qu'affiché dans l'historique du voyageur.
 *
 * Toutes les relations sont traitées comme facultatives : d'anciens tickets
 * peuvent avoir perdu leur instance de voyage.
 *
 * @mixin \App\Models\Ticket\Ticket
 */
class TravelHistoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $instance  = $this->voyageInstance;
        $voyage    = $instance?->voyage;
        $trajet    = $voyage?->trajet;
        $compagnie = $voyage?->compagnie;

        $depart = $this->departureAt($instance, $voyage);
        $statut = $this->statut instanceof StatutTicket ? $this->statut : StatutTicket::tryFrom((string) $this->statut);

        $estPasse = $depart?->isPast() ?? false;

        return [
            'id'            => $this->id,
            'ticket_number' => $this->numero_ticket,
            'status'        => $statut?->value ?? (string) $this->statut,
            'status_color'  => $statut?->getColor(),
            'type'          => $this->type instanceof \BackedEnum ? $this->type->value : $this->type,
            'date'          => $depart?->toDateString(),
            'time'          => $depart?->format('H:i'),
            'departure'     => [
                'city'    => $trajet?->depart?->name,
                'station' => $instance?->gareDepart()?->name,
            ],
            'arrival' => [
                'city'    => $trajet?->arriver?->name,
                'station' => $instance?->gareArrive()?->name,
            ],
            'company' => $compagnie ? [
                'id'    => $compagnie->id,
                'name'  => $compagnie->name,
                'sigle' => $compagnie->sigle,
                'logo'  => $compagnie->logo_uri,
            ] : null,
            'price'   => $this->safePrice(),
            'seat'    => $this->numero_chaise,
            'trip_id' => $this->voyage_instance_id,
            'is_past' => $estPasse,
            // Une compagnie ne se note qu'après un voyage effectivement réalisé.
            'can_rate' => $estPasse
                && $compagnie !== null
                && in_array($statut, [StatutTicket::Valider, StatutTicket::Payer], true),
        ];
    }

    /** Date et heure de départ, recomposées depuis l'instance et le voyage. */
    private function departureAt(mixed $instance, mixed $voyage): ?Carbon
    {
        if (! $instance?->date) {
            return $this->date ? Carbon::parse($this->date) : null;
        }

        $heure = $instance->heure ?? $voyage?->heure;

        return Carbon::parse(
            Carbon::parse($instance->date)->toDateString().' '.($heure ? Carbon::parse($heure)->format('H:i:s') : '00:00:00')
        );
    }

    /** Le prix dépend de relations qui peuvent manquer sur d'anciens tickets. */
    private function safePrice(): float
    {
        try {
            return (float) $this->prix();
        } catch (\Throwable) {
            return 0.0;
        }
    }
}
