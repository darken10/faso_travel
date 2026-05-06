<?php

namespace App\Services\Voyage;

use App\Enums\JoursSemain;
use App\Enums\StatutVoyageInstance;
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

    public function createForCompagnie(int $compagnieId, int $days = 30): array
    {
        $created  = 0;
        $skipped  = 0;
        $today    = now()->startOfDay();

        $voyages = Voyage::withoutGlobalScopes()
            ->where('compagnie_id', $compagnieId)
            ->with('cares')
            ->get();

        for ($i = 0; $i < $days; $i++) {
            $dateVoyage = $today->copy()->addDays($i);

            foreach ($voyages as $voyage) {
                $daysArray = is_array($voyage->days) ? $voyage->days : [];

                $matchesToday = $voyage->is_quotidient
                    || in_array(JoursSemain::ToutLesJours->value, $daysArray)
                    || (!empty($daysArray) && VoyagesInstanceHelpers::isVoyageExisteInThisDate($dateVoyage, $daysArray));

                if (!$matchesToday) {
                    continue;
                }

                $lastCare = $voyage->cares->last();

                $instance = VoyageInstance::firstOrCreate(
                    [
                        'voyage_id' => $voyage->id,
                        'date'      => $dateVoyage->toDateString(),
                    ],
                    [
                        'heure'       => $voyage->heure?->format('H:i:s'),
                        'nb_place'    => $voyage->nb_pace ?: ($lastCare?->number_place ?? 0),
                        'care_id'     => $lastCare?->id,
                        'chauffer_id' => null,
                        'statut'      => StatutVoyageInstance::DISPONIBLE->value,
                        'prix'        => $voyage->prix,
                        'classe_id'   => $voyage->classe_id,
                    ]
                );

                if ($instance->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
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
