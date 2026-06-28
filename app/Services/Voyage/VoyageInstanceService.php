<?php

namespace App\Services\Voyage;

use App\Enums\JoursSemain;
use App\Enums\StatutVoyageInstance;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Chauffer;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Helper\VoyagesInstanceHelpers;
use Illuminate\Database\Eloquent\Builder;

class VoyageInstanceService
{
    public function __construct()
    {
    }

    public function createAll()
    {

        $joursAvenir = 30;
        $aujourdHui = now();
        for ($i=0; $i < $joursAvenir; $i++) {
            $dateVoyage = $aujourdHui->copy()->addDays($i);
            Voyage::all()->each(function (Voyage $voyage) use ($dateVoyage) {
                if (in_array(JoursSemain::ToutLesJours->value, $voyage->days) || VoyagesInstanceHelpers::isVoyageExisteInThisDate($dateVoyage,$voyage->days)){

                    $lastCareOld = $voyage->cares->last();
                    VoyageInstance::firstOrCreate([
                        'voyage_id' => $voyage->id,
                        'date' => $dateVoyage,
                        'heure'=> $voyage->heure,
                        'nb_place'=> $voyage->nb_pace ?: ($lastCareOld?->number_place ?? 0),
                        'prix' => $voyage->prix ?? 0,
                        'care_id'=> $lastCareOld?->id ?? null,
                        'chauffer_id'=> null
                    ]);
                }
            });
        }

    }

    /** Génération manuelle pour une compagnie (délègue à generate()). */
    public function createForCompagnie(int $compagnieId, int $days = 30): array
    {
        return $this->generate($days, null, $compagnieId, false);
    }

    /**
     * Génère les instances de voyage sur une fenêtre de jours, avec affectation
     * automatique d'un véhicule et d'un chauffeur disponibles. Utilisé par le
     * cron (toutes compagnies) et par la génération manuelle (une compagnie).
     *
     * @param  int          $days        Nombre de jours à générer (à partir d'aujourd'hui)
     * @param  string|null  $voyageId    Limiter à un voyage précis
     * @param  int|null     $compagnieId Limiter à une compagnie
     * @param  bool         $force       Régénérer même si l'instance existe déjà
     * @return array{created:int, skipped:int}
     */
    public function generate(int $days = 7, ?string $voyageId = null, ?int $compagnieId = null, bool $force = false): array
    {
        $created = 0;
        $skipped = 0;
        $today   = now()->startOfDay();

        $voyages = Voyage::withoutGlobalScopes()
            ->when($compagnieId, fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->when($voyageId, fn ($q) => $q->where('id', $voyageId))
            ->with('cares')
            ->get();

        for ($i = 0; $i < $days; $i++) {
            $dateVoyage = $today->copy()->addDays($i);
            $dateStr    = $dateVoyage->toDateString();

            foreach ($voyages as $voyage) {
                $daysArray = is_array($voyage->days) ? $voyage->days : [];

                $matchesToday = $voyage->is_quotidient
                    || in_array(JoursSemain::ToutLesJours->value, $daysArray)
                    || (!empty($daysArray) && VoyagesInstanceHelpers::isVoyageExisteInThisDate($dateVoyage, $daysArray));

                if (!$matchesToday) {
                    continue;
                }

                $exists = VoyageInstance::where('voyage_id', $voyage->id)
                    ->whereDate('date', $dateStr)
                    ->exists();

                if ($exists && !$force) {
                    $skipped++;
                    continue;
                }

                if ($exists && $force) {
                    VoyageInstance::where('voyage_id', $voyage->id)
                        ->whereDate('date', $dateStr)
                        ->delete();
                }

                $heure = $voyage->heure?->format('H:i:s');
                [$careId, $chaufferId, $nbPlace] = $this->assignResources($voyage, $dateStr, $heure);

                VoyageInstance::create([
                    'voyage_id'   => $voyage->id,
                    'date'        => $dateStr,
                    'heure'       => $heure,
                    'nb_place'    => $nbPlace,
                    'care_id'     => $careId,
                    'chauffer_id' => $chaufferId,
                    'statut'      => StatutVoyageInstance::DISPONIBLE->value,
                    'prix'        => $voyage->prix,
                    'classe_id'   => $voyage->classe_id,
                ]);

                $created++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Affecte automatiquement un véhicule et un chauffeur libres pour ce
     * créneau (date + heure), en évitant les conflits. Retourne aussi le
     * nombre de places déduit du véhicule choisi.
     *
     * @return array{0: int|null, 1: string|null, 2: int}  [care_id, chauffer_id, nb_place]
     */
    private function assignResources(Voyage $voyage, string $dateStr, ?string $heure): array
    {
        // Véhicules candidats : ceux liés au voyage, sinon ceux de la compagnie.
        $cares = $voyage->cares->isNotEmpty()
            ? $voyage->cares
            : Care::where('compagnie_id', $voyage->compagnie_id)->get();

        $chosenCare = null;
        foreach ($cares as $care) {
            $busy = VoyageInstance::whereDate('date', $dateStr)
                ->where('heure', $heure)
                ->where('care_id', $care->id)
                ->exists();
            if (!$busy) {
                $chosenCare = $care;
                break;
            }
        }
        // Tous occupés → on retombe sur le dernier véhicule du voyage.
        $chosenCare ??= $cares->last();

        // Chauffeurs de la compagnie, libres sur ce créneau.
        $chaufferId = null;
        $chauffeurs = Chauffer::where('compagnie_id', $voyage->compagnie_id)->get();
        foreach ($chauffeurs as $chauffeur) {
            $busy = VoyageInstance::whereDate('date', $dateStr)
                ->where('heure', $heure)
                ->where('chauffer_id', $chauffeur->id)
                ->exists();
            if (!$busy) {
                $chaufferId = $chauffeur->id;
                break;
            }
        }

        $nbPlace = $voyage->nb_pace ?: ($chosenCare?->number_place ?? 0);

        return [$chosenCare?->id, $chaufferId, $nbPlace];
    }

    public function getVoyageInstanceWithBasicRelations(string $id)
    {
        return VoyageInstance::with([
            'voyage.trajet.depart',
            'voyage.trajet.arriver',
            'voyage.compagnie',
            'chauffer',
            'care'
        ])->findOrFail($id);
    }

    public function getVoyageInstanceWithFullDetails(string $id)
    {
        return VoyageInstance::with([
            'voyage.trajet.depart',
            'voyage.trajet.arriver',
            'voyage.compagnie',
            'voyage.classe',
            'voyage.conforts',
            'chauffer',
            'care',
            'tickets' => function($query) {
                $query->with(['user', 'autre_personne', 'payements']);
            }
        ])->findOrFail($id);
    }

    public function getAvailableVoyages(): Builder
    {
        return VoyageInstance::disponibles()
            ->with([
                'voyage.trajet.depart',
                'voyage.trajet.arriver',
                'voyage.compagnie',
                'voyage.classe'
            ]);
    }
}
